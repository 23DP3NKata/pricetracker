package com.pricetracker;

import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;

public class PriceChecker {
    public static Product fetchProductDetails(String url) {
        try {
            Document doc = Jsoup.connect(url)
                    .userAgent("Mozilla/5.0")
                    .timeout(2000)
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
                return new Product("Unknown Product", url, 0.0, "https://cdn2.iconfinder.com/data/icons/packing/80/shipping-34-512.png");
            }
        } catch (Exception e) {
            return null;
        }
    }

    private static Product extractDetails(Element nameElement, Element priceElement, Element imageElement, String url) {
        String name = "Unknown Product";
        double price = 0.0;
        String imageUrl = "https://cdn2.iconfinder.com/data/icons/packing/80/shipping-34-512.png";

        if (nameElement != null) {
            name = nameElement.text();
        }

        if (priceElement != null) {
            String rawPrice = priceElement.text().replaceAll("[^0-9,\\.]", "");

            if (rawPrice.contains(",") && !rawPrice.contains(".")) {
                rawPrice = rawPrice.replace(",", ".");
            } else {
                rawPrice = rawPrice.replace(",", "");
            }

            try {
                price = Double.parseDouble(rawPrice);
            } catch (NumberFormatException e) {
                price = 0.0;
            }
        }

        if (imageElement != null) {
            imageUrl = imageElement.absUrl("src");
        }

        return new Product(name, url, price, imageUrl);
    }
}
