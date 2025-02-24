package com.pricetracker;

import javafx.application.Application;
import javafx.stage.Stage;
import java.util.ArrayList;
import java.util.List;

// mvn clean javafx:run
// .\mvnw.cmd clean install

public class PriceTrackerApp extends Application {
    private final List<Product> products = new ArrayList<>();
    private UIHandler uiHandler;

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
    }

    public UIHandler getUIHandler() {
        return uiHandler;
    }
}
