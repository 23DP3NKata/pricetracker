package com.pricetracker;

public class Product {
    String name;
    String url;
    double price;
    String imageUrl;

    public Product(String name, String url, double price, String imageUrl) {
        this.name = name;
        this.url = url;
        this.price = price;
        this.imageUrl = imageUrl;
    }
}
