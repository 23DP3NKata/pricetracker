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

        startLayout.getStyleClass().add("start-window");

        Label welcomeLabel = new Label("Select an action:");
        Button newListButton = new Button("Create New List");
        Button loadListButton = new Button("Load Existing List");

        newListButton.setPrefWidth(200);
        loadListButton.setPrefWidth(200);

        newListButton.setOnAction(e -> showNewListDialog(primaryStage));
        loadListButton.setOnAction(e -> showLoadListDialog(primaryStage));

        startLayout.getChildren().addAll(welcomeLabel, newListButton, loadListButton);

        Scene scene = new Scene(startLayout, 300, 200);
        scene.getStylesheets().add(getClass().getResource("/styles.css").toExternalForm());
        primaryStage.setScene(scene);
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

                FileChooser fileChooser = new FileChooser();
                fileChooser.setTitle("Save List As");
                fileChooser.getExtensionFilters().add(new FileChooser.ExtensionFilter("JSON Files", "*.json"));
                fileChooser.setInitialFileName(name + ".json");

                File file = fileChooser.showSaveDialog(primaryStage);
                if (file != null) {
                    app.setCurrentProductList(newList, file.getAbsolutePath());
                    FileManager fileManager = new FileManager();
                    try {
                        fileManager.writeProductList(newList, file.getAbsolutePath());
                        System.out.println("List saved as " + file.getAbsolutePath());
                    } catch (IOException e) {
                        System.err.println("Error saving list: " + e.getMessage());
                    }

                    primaryStage.setScene(app.getUIHandler().createMainScene());
                }
            }
        });
    }

    private void showLoadListDialog(Stage primaryStage) {
        FileChooser fileChooser = new FileChooser();
        fileChooser.setTitle("Open List");
        fileChooser.getExtensionFilters().add(new FileChooser.ExtensionFilter("JSON Files", "*.json"));

        File file = fileChooser.showOpenDialog(primaryStage);
        if (file != null) {
            app.loadProductList(file.getAbsolutePath());
            primaryStage.setScene(app.getUIHandler().createMainScene());
        }
    }
}
