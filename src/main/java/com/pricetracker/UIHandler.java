package com.pricetracker;

import javafx.concurrent.Task;
import javafx.geometry.Insets;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.image.Image;
import javafx.scene.image.ImageView;
import javafx.scene.layout.*;

public class UIHandler {
    private final PriceTrackerApp app;
    private final VBox productContainer = new VBox(10);

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

        TextField serchField = new TextField();
        serchField.setPromptText("Search products");

        ChoiceBox<String> sortChoiceBox = new ChoiceBox<>();
        sortChoiceBox.getItems().addAll("Name", "Price");
        sortChoiceBox.setValue("Name");

        HBox topMenu = new HBox(10, addButton, serchField, sortChoiceBox);
        topMenu.setPadding(new Insets(10));

        root.setTop(topMenu);
        root.setCenter(scrollPane);
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

        Button deleteButton = new Button("Delete");
        deleteButton.setOnAction(e -> {
            productContainer.getChildren().remove(productBox);
            app.removeProduct(product);
        });

        deleteButton.setPrefWidth(60);

        productBox.getChildren().addAll(imageView, nameLabel, priceLabel, deleteButton);
        productContainer.getChildren().add(productBox);
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
}
