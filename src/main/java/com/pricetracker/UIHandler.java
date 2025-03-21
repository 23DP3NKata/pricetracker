package com.pricetracker;

import javafx.concurrent.Task;
import javafx.geometry.Insets;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.image.Image;
import javafx.scene.image.ImageView;
import javafx.scene.layout.*;
import javafx.scene.text.Font;
import javafx.scene.text.Text;

import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.List;
import java.util.stream.Collectors;

public class UIHandler {
    private final PriceTrackerApp app;
    private final VBox productContainer = new VBox(10);
    private final Text lastUpdateText = new Text("Last update: N/A");
    private List<HBox> allProducts;

    public UIHandler(PriceTrackerApp app) {
        this.app = app;

        Font.loadFont(getClass().getResource("/fonts/Poppins-Regular.ttf").toExternalForm(), 14);
        this.allProducts = new ArrayList<>();
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
        searchField.textProperty().addListener((observable, oldValue, newValue) -> filterProducts(newValue));
        searchField.getStyleClass().add("searchField");

        ChoiceBox<String> sortChoiceBox = new ChoiceBox<>();
        sortChoiceBox.getItems().addAll(
            "Name A-Z", 
            "Name Z-A", 
            "Price Low-High", 
            "Price High-Low", 
            "Date New-Old", 
            "Date Old-New"
        );
        sortChoiceBox.setValue("Name A-Z");
        sortChoiceBox.getSelectionModel().selectedItemProperty().addListener((observable, oldValue, newValue) -> sortProducts(newValue));
        sortProducts("Name A-Z");
        sortChoiceBox.getStyleClass().add("sortChoiceBox");

        HBox topMenu = new HBox(10, addButton, searchField, sortChoiceBox);
        topMenu.setPadding(new Insets(10));

        Button updateButton = new Button("Update Prices");
        updateButton.setOnAction(e -> app.updatePricesManually());
        lastUpdateText.getStyleClass().add("lastUpdateText");
        
        HBox bottomMenu = new HBox(10, lastUpdateText, updateButton);
        bottomMenu.setPadding(new Insets(10));

        root.setTop(topMenu);
        root.setCenter(scrollPane);
        root.setBottom(bottomMenu);

        Scene scene = new Scene(root, 1200, 600);

        scene.getStylesheets().add(getClass().getResource("/styles.css").toExternalForm());

        return scene;
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
        imageView.setFitHeight(70);
        imageView.setFitWidth(70);

        Label nameLabel = new Label(product.getName());
        Label priceLabel = new Label("€" + product.getPrice());

        VBox textContainer = new VBox(5, nameLabel, priceLabel);

        Region spacer = new Region();
        HBox.setHgrow(spacer, Priority.ALWAYS);

        Button priceHistoryButton = new Button("Price History");
        priceHistoryButton.setOnAction(e -> showPriceHistory(product));

        Button settingsButton = new Button("Settings");
        settingsButton.setOnAction(e -> showSettingsDialog(product, productBox));

        priceHistoryButton.setPrefWidth(100);
        settingsButton.setPrefWidth(100);

        HBox buttonBox = new HBox(10, priceHistoryButton, settingsButton);
        productBox.getChildren().addAll(imageView, textContainer, spacer, buttonBox);

        productBox.setUserData(product);

        productContainer.getChildren().add(productBox);

        if (allProducts == null) {
            allProducts = new ArrayList<>();
        }
        allProducts.add(productBox);
    }

    private void showPriceHistory(Product product) {
        Alert alert = new Alert(Alert.AlertType.INFORMATION);
        alert.setTitle("Price History");
        alert.setHeaderText(product.getName());

        StringBuilder history = new StringBuilder();
        DateTimeFormatter formatter = DateTimeFormatter.ofPattern("dd.MM.yyyy HH:mm");
        for (PriceHistoryEntry entry : product.getPriceHistory()) {
            history.append("Price: €").append(entry.getPrice())
                   .append(", Date: ").append(entry.getDate().format(formatter)).append("\n");
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
        if (allProducts != null) {
            allProducts.clear();
        }
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

    private void sortProducts(String sortOption) {
        List<HBox> sortedProducts = productContainer.getChildren().stream()
                .map(node -> (HBox) node)
                .sorted(getComparator(sortOption))
                .collect(Collectors.toList());

        productContainer.getChildren().setAll(sortedProducts);
    }

    private Comparator<HBox> getComparator(String sortOption) {
        switch (sortOption) {
            case "Name A-Z":
                return Comparator.comparing((HBox hbox) -> {
                    VBox textContainer = (VBox) hbox.getChildren().get(1);
                    Label nameLabel = (Label) textContainer.getChildren().get(0);
                    return nameLabel.getText();
                });
            case "Name Z-A":
                return Comparator.comparing((HBox hbox) -> {
                    VBox textContainer = (VBox) hbox.getChildren().get(1);
                    Label nameLabel = (Label) textContainer.getChildren().get(0);
                    return nameLabel.getText();
                }).reversed();
            case "Price Low-High":
                return Comparator.comparingDouble((HBox hbox) -> {
                    VBox textContainer = (VBox) hbox.getChildren().get(1);
                    Label priceLabel = (Label) textContainer.getChildren().get(1);
                    return Double.parseDouble(priceLabel.getText().replace("€", ""));
                });
            case "Price High-Low":
                return Comparator.comparingDouble((HBox hbox) -> {
                    VBox textContainer = (VBox) hbox.getChildren().get(1);
                    Label priceLabel = (Label) textContainer.getChildren().get(1);
                    return Double.parseDouble(priceLabel.getText().replace("€", ""));
                }).reversed();
            case "Date New-Old":
                return Comparator.comparing((HBox hbox) -> {
                    Product product = (Product) hbox.getUserData();
                    return product.getCreatedAt();
                }).reversed();
            case "Date Old-New":
                return Comparator.comparing((HBox hbox) -> {
                    Product product = (Product) hbox.getUserData();
                    return product.getCreatedAt();
                });
            default:
                return Comparator.comparing((HBox hbox) -> {
                    VBox textContainer = (VBox) hbox.getChildren().get(1);
                    Label nameLabel = (Label) textContainer.getChildren().get(0);
                    return nameLabel.getText();
                });
        }
    }

    private void filterProducts(String searchText) {
        if (allProducts == null || allProducts.isEmpty()) {
            return;
        }

        List<HBox> filteredProducts = allProducts.stream()
                .filter(hbox -> {
                    VBox textContainer = (VBox) hbox.getChildren().get(1);
                    Label nameLabel = (Label) textContainer.getChildren().get(0);
                    return nameLabel.getText().toLowerCase().contains(searchText.toLowerCase());
                })
                .collect(Collectors.toList());

        productContainer.getChildren().setAll(filteredProducts);
    }
}