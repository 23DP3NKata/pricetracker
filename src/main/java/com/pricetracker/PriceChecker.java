package com.pricetracker;

import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;

import javafx.application.Platform;

import java.io.IOException;
import java.time.LocalDateTime;

public class PriceChecker {
    public static Product fetchProductDetails(String url) {
        try {
            Document doc = Jsoup.connect(url)
                    .userAgent("Mozilla/5.0")
                    .timeout(15000)
                    .get();

            if (url.contains("rdveikals.lv")) {
                Element nameElement = doc.selectFirst(".product-info h1");
                Element priceElement = doc.selectFirst(".price strong");
                Element imageElement = doc.selectFirst(".carousel_center_img img");
                return extractDetails(nameElement, priceElement, imageElement, url);
            } else if (url.contains("1a.lv")) {
                Element nameElement = doc.selectFirst(".product-righter.google-rich-snippet h1");
                Element priceElement = doc.selectFirst(".price span");
                Element imageElement = doc.selectFirst(".products-gallery-slider__slide-inner img");
                return extractDetails(nameElement, priceElement, imageElement, url);
            } else {
                return new Product("Unknown Product", url, 0.0, "https://cdn2.iconfinder.com/data/icons/packing/80/shipping-34-512.png", "Unknown Shop");
            }
        } catch (Exception e) {
            return null;
        }
    }

    public static void updateProductPrice(Product product) {
        int retries = 3;
        int attempt = 0;

        while (attempt < retries) {
            try {
                Document doc = Jsoup.connect(product.getUrl())
                        .userAgent("Mozilla/5.0")
                        .timeout(15000)
                        .get();

                double newPrice = 0.0;

                if (product.getUrl().contains("rdveikals.lv")) {
                    Element priceElement = doc.selectFirst(".price strong");
                    if (priceElement != null) {
                        newPrice = parsePrice(priceElement.text());
                    }
                } else if (product.getUrl().contains("1a.lv")) {
                    Element priceElement = doc.selectFirst(".price span");
                    if (priceElement != null) {
                        newPrice = parsePrice(priceElement.text());
                    }
                }

                if (newPrice != product.getPrice()) {
                    System.out.println("Price changed for " + product.getName() + " from " + product.getPrice() + " to " + newPrice);

                    product.setPrice(newPrice);
                    product.setUpdatedAt(LocalDateTime.now());
                    product.addPriceHistoryEntry(new PriceHistoryEntry(newPrice, LocalDateTime.now()));

                    Platform.runLater(() -> {
                        PriceTrackerApp app = PriceTrackerApp.getInstance();
                        if (app != null) {
                            app.getUIHandler().updateProductInUI(product);
                        }
                    });
                }
                break;
            } catch (IOException e) {
                attempt++;
                System.err.println("Attempt " + attempt + " failed for product: " + product.getName());
                System.err.println("Error updating price for product: " + product.getName() + " (URL: " + product.getUrl() + ")");
                if (attempt == retries) {
                    System.err.println("Failed to update price after " + retries + " attempts for product: " + product.getName());
                }
            }
        }
    }

    private static double parsePrice(String priceText) {
        String rawPrice = priceText.replaceAll("[^0-9,\\.]", "");

        if (rawPrice.contains(",") && rawPrice.contains(".")) {
            rawPrice = rawPrice.replace(".", "").replace(",", ".");
        } else if (rawPrice.contains(",") && !rawPrice.contains(".")) {
            rawPrice = rawPrice.replace(",", ".");
        } else {
            rawPrice = rawPrice.replace(",", "");
        }

        try {
            return Double.parseDouble(rawPrice);
        } catch (NumberFormatException e) {
            return 0.0;
        }
    }

    private static Product extractDetails(Element nameElement, Element priceElement, Element imageElement, String url) {
        String name = "Unknown Product";
        double price = 0.0;
        String imageUrl = "https://cdn2.iconfinder.com/data/icons/packing/80/shipping-34-512.png";
        String shop = "Unknown Shop";

        if (nameElement != null) {
            name = capitalize(nameElement.text());
        }

        if (priceElement != null) {
            price = parsePrice(priceElement.text());
        }

        if (imageElement != null) {
            imageUrl = imageElement.absUrl("src");
        }

        if (url.contains("rdveikals.lv")) {
            shop = "RD Electronics";
        } else if (url.contains("1a.lv")) {
            shop = "1a.lv";
        }

        Product product = new Product(name, url, price, imageUrl, shop);
        product.setCreatedAt(LocalDateTime.now());
        product.setUpdatedAt(LocalDateTime.now());
        product.addPriceHistoryEntry(new PriceHistoryEntry(price, LocalDateTime.now()));

        return product;
    }

    private static String capitalize(String text) {
        if (text == null || text.isEmpty()) {
            return text;
        }
        return text.substring(0, 1).toUpperCase() + text.substring(1).toLowerCase();
    }
}
