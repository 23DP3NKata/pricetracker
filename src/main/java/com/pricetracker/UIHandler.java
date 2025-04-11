package com.pricetracker;

import javafx.concurrent.Task;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.image.Image;
import javafx.scene.image.ImageView;
import javafx.scene.input.Clipboard;
import javafx.scene.input.ClipboardContent;
import javafx.scene.layout.*;
import javafx.scene.text.Font;
import javafx.scene.text.Text;
import javafx.scene.text.TextAlignment;
import javafx.scene.Cursor;
import javafx.scene.Node;
import javafx.stage.FileChooser;
import javafx.beans.value.ChangeListener;
import javafx.beans.value.ObservableValue;
import org.controlsfx.control.RangeSlider;

import java.io.File;
import java.io.IOException;
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
    private String style = "/stylesDark.css";

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
            "Date Old-New",
            "Updated New-Old",
            "Updated Old-New"
        );
        sortChoiceBox.setValue("Price High-Low");
        sortChoiceBox.getSelectionModel().selectedItemProperty().addListener((observable, oldValue, newValue) -> sortProducts(newValue));
        sortProducts("Price High-Low");
        sortChoiceBox.getStyleClass().add("sortChoiceBox");

        // Price filter
        Label priceFilterLabel = new Label("");
        TextField minPriceField = new TextField();
        minPriceField.setPromptText("Min");
        minPriceField.setPrefWidth(60);

        TextField maxPriceField = new TextField();
        maxPriceField.setPromptText("Max");
        maxPriceField.setPrefWidth(60);

        RangeSlider priceRangeSlider = new RangeSlider(0, 5000, 0, 1000);
        priceRangeSlider.setShowTickLabels(true);
        priceRangeSlider.setShowTickMarks(true);
        priceRangeSlider.setMajorTickUnit(500);
        priceRangeSlider.setBlockIncrement(50);

        priceRangeSlider.lowValueProperty().addListener((observable, oldValue, newValue) -> {
            minPriceField.setText(String.format("%.0f", newValue.doubleValue()));
            filterProductsByPrice(minPriceField.getText(), maxPriceField.getText());
        });

        priceRangeSlider.highValueProperty().addListener((observable, oldValue, newValue) -> {
            maxPriceField.setText(String.format("%.0f", newValue.doubleValue()));
            filterProductsByPrice(minPriceField.getText(), maxPriceField.getText());
        });

        minPriceField.textProperty().addListener((observable, oldValue, newValue) -> {
            double minValue = newValue.isEmpty() ? 0 : Double.parseDouble(newValue);
            priceRangeSlider.setLowValue(minValue);
            filterProductsByPrice(newValue, maxPriceField.getText());
        });

        maxPriceField.textProperty().addListener((observable, oldValue, newValue) -> {
            double maxValue = newValue.isEmpty() ? 1000 : Double.parseDouble(newValue);
            priceRangeSlider.setHighValue(maxValue);
            filterProductsByPrice(minPriceField.getText(), newValue);
        });

        HBox priceFilterBox = new HBox(10, priceFilterLabel, minPriceField, priceRangeSlider, maxPriceField);
        priceFilterBox.setAlignment(Pos.CENTER_LEFT);

        TabPane tabPane = new TabPane();
        tabPane.setTabClosingPolicy(TabPane.TabClosingPolicy.ALL_TABS);

        tabPane.getSelectionModel().selectedItemProperty().addListener(new ChangeListener<Tab>() {
            @Override
            public void changed(ObservableValue<? extends Tab> observable, Tab oldTab, Tab newTab) {
                if (newTab != null) {
                    ProductList selectedList = (ProductList) newTab.getUserData();
                    if (selectedList != null) {
                        loadProductsIntoUI(selectedList);
                    }
                }
            }
        });

        tabPane.getStyleClass().add("tab-pane");

        Button newListButton = new Button("New List");
        newListButton.setOnAction(e -> createNewTab(tabPane));

        Button loadListButton = new Button("Load List");
        loadListButton.setOnAction(e -> loadListIntoTab(tabPane));

        HBox tabControls = new HBox(10, tabPane);
        tabControls.setPadding(new Insets(10));
        tabControls.setAlignment(Pos.CENTER_LEFT);

        HBox topMenu = new HBox(10, addButton, searchField, sortChoiceBox, priceFilterBox, newListButton, loadListButton);
        topMenu.setPadding(new Insets(10));

        Button updateButton = new Button("Update Prices");
        updateButton.setOnAction(e -> app.updatePricesManually());
        lastUpdateText.getStyleClass().add("lastUpdateText");
        lastUpdateText.setTextAlignment(TextAlignment.CENTER);


        Button switchThemeButton = new Button("Switch Theme");
        switchThemeButton.setOnAction(e -> {
            if (style.equals("/styles.css")) {
                style = "/stylesDark.css";
            } else {
                style = "/styles.css";
            }
            Scene currentScene = switchThemeButton.getScene();
            currentScene.getStylesheets().clear();
            currentScene.getStylesheets().add(getClass().getResource(style).toExternalForm());
        });
        
        Region spacer = new Region();
        HBox.setHgrow(spacer, Priority.ALWAYS);
        
        HBox bottomMenu = new HBox(10, lastUpdateText, updateButton, spacer, switchThemeButton);
        bottomMenu.setPadding(new Insets(10));
        bottomMenu.setAlignment(Pos.CENTER_LEFT);

        root.setTop(new VBox(topMenu, tabControls));
        root.setCenter(scrollPane);
        root.setBottom(bottomMenu);

        Scene scene = new Scene(root, 1200, 600);

        scene.getStylesheets().add(getClass().getResource(style).toExternalForm());

        return scene;
    }

    private void createNewTab(TabPane tabPane) {
        TextInputDialog dialog = new TextInputDialog();
        dialog.setTitle("New List");
        dialog.setHeaderText("Enter a name for the new list:");
        dialog.setContentText("List Name:");

        dialog.getDialogPane().getStylesheets().add(getClass().getResource(style).toExternalForm());
        dialog.getDialogPane().getStyleClass().add("dialog-pane");

        dialog.showAndWait().ifPresent(name -> {
            if (!name.trim().isEmpty()) {
                ProductList newList = new ProductList(name);

                FileChooser fileChooser = new FileChooser();
                fileChooser.setTitle("Save List As");
                fileChooser.getExtensionFilters().add(new FileChooser.ExtensionFilter("JSON Files", "*.json"));
                fileChooser.setInitialFileName(name + ".json");

                File file = fileChooser.showSaveDialog(null);
                if (file != null) {
                    app.setCurrentProductList(newList, file.getAbsolutePath());
                    FileManager fileManager = new FileManager();
                    try {
                        fileManager.writeProductList(newList, file.getAbsolutePath());
                    } catch (IOException e) {
                        System.err.println("Error saving list: " + e.getMessage());
                    }

                    Tab newTab = new Tab(name);
                    newTab.setUserData(newList);
                    tabPane.getTabs().add(newTab);
                    tabPane.getSelectionModel().select(newTab);
                }
            }
        });
    }

    private void loadListIntoTab(TabPane tabPane) {
        FileChooser fileChooser = new FileChooser();
        fileChooser.setTitle("Open List");
        fileChooser.getExtensionFilters().add(new FileChooser.ExtensionFilter("JSON Files", "*.json"));

        File file = fileChooser.showOpenDialog(null);
        if (file != null) {
            app.loadProductList(file.getAbsolutePath());

            ProductList loadedList = app.getCurrentProductList();
            if (loadedList != null) {
                Tab newTab = new Tab(loadedList.getName());
                newTab.setUserData(loadedList);
                tabPane.getTabs().add(newTab);
                tabPane.getSelectionModel().select(newTab);
            }
        }
    }

    private void loadProductsIntoUI(ProductList productList) {
        clearProductUI();
        for (Product product : productList.getProducts()) {
            addProductToUI(product);
        }
    }

    private void openAddProductDialog() {
        Dialog<String> dialog = new Dialog<>();
        dialog.setTitle("Add Product");

        TextField linkField = new TextField();
        linkField.setPromptText("Enter product URL");

        Text availableShops = new Text();
        availableShops.setText("Available shops: rdveikals.lv, 1a.lv");
        availableShops.setStyle("-fx-fill: white; -fx-font-size: 12px;");

        GridPane grid = new GridPane();
        grid.setPadding(new Insets(10));
        grid.setVgap(10);
        grid.add(new Label("Product URL: "), 0, 0);
        grid.add(linkField, 1, 0);
        grid.add(availableShops, 0, 1, 2, 1);

        dialog.getDialogPane().setContent(grid);
        dialog.getDialogPane().getButtonTypes().addAll(ButtonType.OK, ButtonType.CANCEL);

        dialog.getDialogPane().getStylesheets().add(getClass().getResource(style).toExternalForm());
        dialog.getDialogPane().getStyleClass().add("dialog-pane");

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
        imageView.setFitHeight(90);
        imageView.setFitWidth(90);

        Label nameLabel = new Label(product.getName());
        nameLabel.setCursor(Cursor.HAND);
        nameLabel.setWrapText(true);
        nameLabel.setMaxWidth(700);
        nameLabel.setOnMouseClicked(event -> copyToClipboard(product.getUrl()));

        Label priceLabel = new Label("€" + product.getPrice());

        Label shopLabel = new Label(product.getShop());
        shopLabel.setStyle("-fx-font-size: 14px;");

        VBox textContainer = new VBox(5, nameLabel, priceLabel, shopLabel);

        Region spacer = new Region();
        HBox.setHgrow(spacer, Priority.ALWAYS);

        Button deleteButton = new Button("Delete");
        deleteButton.setOnAction(e -> {
            productContainer.getChildren().remove(productBox);
            app.removeProduct(product);
        });

        Button priceHistoryButton = new Button("Price History");
        priceHistoryButton.setOnAction(e -> showPriceHistory(product));

        Button settingsButton = new Button("Info");
        settingsButton.setOnAction(e -> showSettingsDialog(product, productBox));

        priceHistoryButton.setPrefWidth(150);
        settingsButton.setPrefWidth(100);

        HBox buttonBox = new HBox(10, priceHistoryButton, deleteButton, settingsButton);
        productBox.getChildren().addAll(imageView, textContainer, spacer, buttonBox);

        productBox.setUserData(product);

        productContainer.getChildren().add(productBox);

        if (allProducts == null) {
            allProducts = new ArrayList<>();
        }
        allProducts.add(productBox);
    }

    private void copyToClipboard(String text) {
        Clipboard clipboard = Clipboard.getSystemClipboard();
        ClipboardContent content = new ClipboardContent();
        content.putString(text);
        clipboard.setContent(content);
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

        TextArea textArea = new TextArea(history.toString());
        textArea.setWrapText(true);
        textArea.setEditable(false);
        textArea.setFocusTraversable(false);
        textArea.setPrefWidth(400);
        textArea.setPrefHeight(200);

        alert.getDialogPane().setContent(textArea);
        alert.getDialogPane().getStylesheets().add(getClass().getResource(style).toExternalForm());
        alert.getDialogPane().getStyleClass().add("dialog-pane");

        alert.showAndWait();
    }

    private void showSettingsDialog(Product product, HBox productBox) {
        Dialog<Void> dialog = new Dialog<>();
        dialog.setTitle("Product Settings");

        Text createDate = new Text("Created: " + product.getCreatedAt().format(DateTimeFormatter.ofPattern("dd.MM.yyyy HH:mm")));
        createDate.setStyle("-fx-fill: white;");
        
        Text updateDate = new Text("Last updated: " + product.getUpdatedAt().format(DateTimeFormatter.ofPattern("dd.MM.yyyy HH:mm")));
        updateDate.setStyle("-fx-fill: white;");
        
        ButtonType cancelButtonType = new ButtonType("Cancel", ButtonBar.ButtonData.CANCEL_CLOSE);
        dialog.getDialogPane().getButtonTypes().addAll(cancelButtonType);

        GridPane grid = new GridPane();
        grid.setPadding(new Insets(10));
        grid.setHgap(10);
        grid.setVgap(10);

        grid.add(createDate, 0, 0);
        grid.add(updateDate, 0, 1);

        dialog.getDialogPane().setContent(grid);

        dialog.getDialogPane().getStylesheets().add(getClass().getResource(style).toExternalForm());
        dialog.getDialogPane().getStyleClass().add("dialog-pane");

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
            case "Updated New-Old":
                return Comparator.comparing((HBox hbox) -> {
                    Product product = (Product) hbox.getUserData();
                    return product.getUpdatedAt();
                }).reversed();
            case "Updated Old-New":
                return Comparator.comparing((HBox hbox) -> {
                    Product product = (Product) hbox.getUserData();
                    return product.getUpdatedAt();
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

    private void filterProductsByPrice(String minPriceText, String maxPriceText) {
        double minPrice = minPriceText.isEmpty() ? 0 : Double.parseDouble(minPriceText);
        double maxPrice = maxPriceText.isEmpty() ? Double.MAX_VALUE : Double.parseDouble(maxPriceText);

        List<HBox> filteredProducts = allProducts.stream()
                .filter(hbox -> {
                    VBox textContainer = (VBox) hbox.getChildren().get(1);
                    Label priceLabel = (Label) textContainer.getChildren().get(1);
                    double price = Double.parseDouble(priceLabel.getText().replace("€", ""));
                    return price >= minPrice && price <= maxPrice;
                })
                .collect(Collectors.toList());

        productContainer.getChildren().setAll(filteredProducts);
    }

    public void updateProductInUI(Product product) {
        for (Node node : productContainer.getChildren()) {
            if (node instanceof HBox) {
                HBox productBox = (HBox) node;
                Product existingProduct = (Product) productBox.getUserData();
                if (existingProduct.getUrl().equals(product.getUrl())) {

                    VBox textContainer = (VBox) productBox.getChildren().get(1);
                    Label nameLabel = (Label) textContainer.getChildren().get(0);
                    Label priceLabel = (Label) textContainer.getChildren().get(1);
    
                    nameLabel.setText(product.getName());
                    priceLabel.setText("€" + product.getPrice());
                    break;
                }
            }
        }
    }
}