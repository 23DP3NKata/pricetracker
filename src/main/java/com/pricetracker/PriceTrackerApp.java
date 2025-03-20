package com.pricetracker;

// mvn clean javafx:run
// .\mvnw.cmd clean install

// ./mvnw.cmd javafx:run
// ./mvnw.cmd clean javafx:run

import javafx.application.Application;
import javafx.stage.Stage;

import java.io.IOException;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;
import java.util.Timer;
import java.util.TimerTask;

public class PriceTrackerApp extends Application {
    private final List<Product> products = new ArrayList<>();
    private UIHandler uiHandler;
    private ProductList currentProductList;
    private String currentFilePath;
    private Timer priceUpdateTimer;
    private LocalDateTime lastUpdate;

    public static void main(String[] args) {
        launch(args);
    }

    @Override
    public void start(Stage primaryStage) {
        uiHandler = new UIHandler(this);
        new StartWindow(this).show(primaryStage);
        primaryStage.setTitle("Price Tracker");
        //primaryStage.getIcons().add(new javafx.scene.image.Image(getClass().getResourceAsStream("/images/image.png")));
    }

    public void addProduct(Product product) {
        if (isProductInList(product)) {
            uiHandler.showProductExistsAlert(product);
            return;
        }

        products.add(product);
        uiHandler.addProductToUI(product);
        if (currentProductList != null) {
            currentProductList.addProduct(product);
            saveCurrentProductList();
        }
    }

    private boolean isProductInList(Product product) {
        return products.stream().anyMatch(p -> p.getUrl().equals(product.getUrl()) || p.getName().equals(product.getName()));
    }

    public void removeProduct(Product product) {
        products.remove(product);
        if (currentProductList != null) {
            currentProductList.getProducts().remove(product);
            saveCurrentProductList();
        }
    }

    public void setCurrentProductList(ProductList productList, String filePath) {
        this.currentProductList = productList;
        this.currentFilePath = filePath;
        startPriceUpdateTimer();
    }

    public void saveCurrentProductList() {
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

    public void loadProductList(String filePath) {
        FileManager fileManager = new FileManager();
        try {
            ProductList productList = fileManager.readProductList(filePath);
            setCurrentProductList(productList, filePath);
            uiHandler.clearProductUI();
            for (Product product : productList.getProducts()) {
                uiHandler.addProductToUI(product);
            }
        } catch (IOException e) {
            System.err.println("Error loading list: " + e.getMessage());
        }
    }

    private void startPriceUpdateTimer() {
        if (priceUpdateTimer != null) {
            priceUpdateTimer.cancel();
        }

        priceUpdateTimer = new Timer(true);
        priceUpdateTimer.scheduleAtFixedRate(new TimerTask() {
            @Override
            public void run() {
                updatePrices();
            }
        }, 0, 5 * 60 * 1000); // Update prices every 5 minutes
    }

    public void updatePrices() {
        if (currentProductList != null) {
            for (Product product : currentProductList.getProducts()) {
                PriceChecker.updateProductPrice(product);
            }
            saveCurrentProductList();
            lastUpdate = LocalDateTime.now();
            uiHandler.updateLastUpdateText(lastUpdate);
        }
    }

    public void updatePricesManually() {
        updatePrices();
    }

    public UIHandler getUIHandler() {
        return uiHandler;
    }
}