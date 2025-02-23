package com.pricetracker;

import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;

public class PriceChecker {
    public static Product fetchProductDetails(String url) {
        try {
            Document doc = Jsoup.connect(url)
                    .userAgent("Mozilla/5.0")
                    .timeout(5000)
                    .get();

            String name = "Unknown Product";
            double price = 0.0;
            String imageUrl = "https://via.placeholder.com/100";

            if (url.contains("rdveikals.lv")) {
                Element nameElement = doc.selectFirst(".product-info h1");
                Element priceElement = doc.selectFirst(".price strong");
                Element imageElement = doc.selectFirst(".carousel_center_img img");

                if (nameElement != null) name = nameElement.text();
                if (priceElement != null) price = Double.parseDouble(priceElement.text().replaceAll("[^0-9.]", ""));
                if (imageElement != null) imageUrl = imageElement.absUrl("src");
            }

            return new Product(name, url, price, imageUrl);
        } catch (Exception e) {
            return null;
        }
    }
}
