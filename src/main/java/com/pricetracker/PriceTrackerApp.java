package com.pricetracker;

import javafx.application.Application;
import javafx.stage.Stage;

import java.io.IOException;
import java.util.ArrayList;
import java.util.List;

// mvn clean javafx:run
// .\mvnw.cmd clean install

public class PriceTrackerApp extends Application {
    private final List<Product> products = new ArrayList<>();
    private UIHandler uiHandler;
    private ProductList currentProductList;
    private String currentFilePath;

    public static void main(String[] args) {
        launch(args);
    }

    @Override
    public void start(Stage primaryStage) {
        uiHandler = new UIHandler(this);
        new StartWindow(this).show(primaryStage);
    }

    public void addProduct(Product product) {
        products.add(product);
        uiHandler.addProductToUI(product);
        if (currentProductList != null) {
            currentProductList.addProduct(product);
            saveCurrentProductList();
        }
    }

    public void setCurrentProductList(ProductList productList, String filePath) {
        this.currentProductList = productList;
        this.currentFilePath = filePath;
    }

    private void saveCurrentProductList() {
        if (currentProductList != null && currentFilePath != null) {
            FileManager fileManager = new FileManager();
            try {
                fileManager.writeProductList(currentProductList, currentFilePath);
                System.out.println("List saved as " + currentFilePath);
            } catch (IOException e) {
                System.err.println("Error saving list: " + e.getMessage());
            }
        }
    }

    public UIHandler getUIHandler() {
        return uiHandler;
    }
}
