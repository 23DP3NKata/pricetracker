package com.pricetracker;

import javafx.concurrent.Task;
import javafx.geometry.Insets;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.image.Image;
import javafx.scene.image.ImageView;
import javafx.scene.layout.*;
import javafx.scene.text.Text;

import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;

public class UIHandler {
    private final PriceTrackerApp app;
    private final VBox productContainer = new VBox(10);
    private final Text lastUpdateText = new Text("Last update: N/A");

    public UIHandler(PriceTrackerApp app) {
        this.app = app;
    }

    public Scene createMainScene() {
        BorderPane root = new BorderPane();
        productContainer.setPadding(new Insets(10));

        ScrollPane scrollPane = new ScrollPane(productContainer);
        scrollPane.setFitToWidth(true);

        Button addButton = new Button("Add Product");
        addButton.setOnAction(e -> openAddProductDialog());

        TextField searchField = new TextField();
        searchField.setPromptText("Search products");

        ChoiceBox<String> sortChoiceBox = new ChoiceBox<>();
        sortChoiceBox.getItems().addAll("Name", "Price");
        sortChoiceBox.setValue("Name");

        HBox topMenu = new HBox(10, addButton, searchField, sortChoiceBox);
        topMenu.setPadding(new Insets(10));

        Button updateButton = new Button("Update Prices");
        updateButton.setOnAction(e -> app.updatePricesManually());

        HBox bottomMenu = new HBox(10, lastUpdateText, updateButton);
        bottomMenu.setPadding(new Insets(10));

        root.setTop(topMenu);
        root.setCenter(scrollPane);
        root.setBottom(bottomMenu);
        return new Scene(root, 800, 600);
    }

    private void openAddProductDialog() {
        Dialog<String> dialog = new Dialog<>();
        dialog.setTitle("Add Product");

        TextField linkField = new TextField();
        linkField.setPromptText("Enter product URL");

        GridPane grid = new GridPane();
        grid.setPadding(new Insets(10));
        grid.add(new Label("Product URL: "), 0, 0);
        grid.add(linkField, 1, 0);

        dialog.getDialogPane().setContent(grid);
        dialog.getDialogPane().getButtonTypes().addAll(ButtonType.OK, ButtonType.CANCEL);

        dialog.setResultConverter(button -> button == ButtonType.OK ? linkField.getText() : null);
        dialog.showAndWait().ifPresent(url -> {
            Task<Product> task = new Task<>() {
                @Override
                protected Product call() {
                    return PriceChecker.fetchProductDetails(url);
                }
            };

            task.setOnSucceeded(e -> {
                Product product = task.getValue();
                if (product != null) app.addProduct(product);
            });

            new Thread(task).start();
        });
    }

    public void addProductToUI(Product product) {
        HBox productBox = new HBox(10);
        productBox.setPadding(new Insets(10));

        ImageView imageView = new ImageView(new Image(product.getImageUrl()));
        imageView.setFitHeight(50);
        imageView.setFitWidth(50);

        Label nameLabel = new Label(product.getName());
        Label priceLabel = new Label("€" + product.getPrice());

        Button priceHistoryButton = new Button("Price History");
        priceHistoryButton.setOnAction(e -> showPriceHistory(product));

        Button settingsButton = new Button("Settings");
        settingsButton.setOnAction(e -> showSettingsDialog(product, productBox));

        priceHistoryButton.setPrefWidth(100);
        settingsButton.setPrefWidth(100);

        productBox.getChildren().addAll(imageView, nameLabel, priceLabel, priceHistoryButton, settingsButton);
        productContainer.getChildren().add(productBox);
    }

    private void showPriceHistory(Product product) {
        Alert alert = new Alert(Alert.AlertType.INFORMATION);
        alert.setTitle("Price History");
        alert.setHeaderText(product.getName());

        StringBuilder history = new StringBuilder();
        for (PriceHistoryEntry entry : product.getPriceHistory()) {
            history.append("Price: €").append(entry.getPrice())
                   .append(", Date: ").append(entry.getDate().toString()).append("\n");
        }

        alert.setContentText(history.toString());
        alert.showAndWait();
    }

    private void showSettingsDialog(Product product, HBox productBox) {
        Dialog<Void> dialog = new Dialog<>();
        dialog.setTitle("Product Settings");

        ButtonType deleteButtonType = new ButtonType("Delete", ButtonBar.ButtonData.OK_DONE);
        ButtonType cancelButtonType = new ButtonType("Cancel", ButtonBar.ButtonData.CANCEL_CLOSE);
        dialog.getDialogPane().getButtonTypes().addAll(deleteButtonType, cancelButtonType);

        GridPane grid = new GridPane();
        grid.setPadding(new Insets(10));
        grid.setHgap(10);
        grid.setVgap(10);

        dialog.getDialogPane().setContent(grid);

        dialog.setResultConverter(dialogButton -> {
            if (dialogButton == deleteButtonType) {
                productContainer.getChildren().remove(productBox);
                app.removeProduct(product);
            }
            return null;
        });

        dialog.showAndWait();
    }

    public void clearProductUI() {
        productContainer.getChildren().clear();
    }

    public void showProductExistsAlert(Product product) {
        Alert alert = new Alert(Alert.AlertType.INFORMATION);
        alert.setTitle("Product Exists");
        alert.setHeaderText(null);
        alert.setContentText("The product \"" + product.getName() + "\" already exists in the list.");
        alert.showAndWait();
    }

    public void updateLastUpdateText(LocalDateTime lastUpdate) {
        DateTimeFormatter formatter = DateTimeFormatter.ofPattern("dd.MM.yyyy HH:mm");
        lastUpdateText.setText("Last update: " + lastUpdate.format(formatter));
    }
}
