package com.pricetracker;

import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.layout.VBox;
import javafx.stage.FileChooser;
import javafx.stage.Stage;

import java.io.File;
import java.io.IOException;

public class StartWindow {
    private final PriceTrackerApp app;

    public StartWindow(PriceTrackerApp app) {
        this.app = app;
    }

    public void show(Stage primaryStage) {
        VBox startLayout = new VBox(10);
        startLayout.setPadding(new Insets(20));
        startLayout.setAlignment(Pos.CENTER);

        Label welcomeLabel = new Label("Select an action:");
        Button newListButton = new Button("Create New List");
        Button loadListButton = new Button("Load Existing List");

        newListButton.setPrefWidth(200);
        loadListButton.setPrefWidth(200);

        newListButton.setOnAction(e -> showNewListDialog(primaryStage));
        loadListButton.setOnAction(e -> showLoadListDialog(primaryStage));

        startLayout.getChildren().addAll(welcomeLabel, newListButton, loadListButton);
        primaryStage.setScene(new Scene(startLayout, 300, 200));
        primaryStage.setTitle("PriceTracker");
        primaryStage.show();
    }

    private void showNewListDialog(Stage primaryStage) {
        TextInputDialog dialog = new TextInputDialog();
        dialog.setTitle("New List");
        dialog.setHeaderText("Enter a name for the new list:");
        dialog.setContentText("List Name:");

        dialog.showAndWait().ifPresent(name -> {
            if (!name.trim().isEmpty()) {
                System.out.println("New list created: " + name);

                ProductList newList = new ProductList(name);
                
                FileManager fileManager = new FileManager();
                try {
                    fileManager.writeProductList(newList);
                    System.out.println("List saved as " + name + ".json");
                } catch (IOException e) {
                    System.err.println("Error saving list: " + e.getMessage());
                }

                primaryStage.setScene(app.getUIHandler().createMainScene());
            }
        });
    }


    private void showLoadListDialog(Stage primaryStage) {
        FileChooser fileChooser = new FileChooser();
        fileChooser.setTitle("Select a list file");
        fileChooser.getExtensionFilters().add(new FileChooser.ExtensionFilter("Text Files", "*.txt"));

        File file = fileChooser.showOpenDialog(primaryStage);
        if (file != null) {
            System.out.println("Loaded list from file: " + file.getName());
            primaryStage.setScene(app.getUIHandler().createMainScene());
        }
    }
}
