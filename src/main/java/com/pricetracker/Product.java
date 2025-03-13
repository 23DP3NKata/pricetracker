package com.pricetracker;

import com.fasterxml.jackson.annotation.JsonProperty;

public class Product {
    @JsonProperty
    private String name;
    @JsonProperty
    private String url;
    @JsonProperty
    private double price;
    @JsonProperty
    private String imageUrl;

    public Product() {
    }

    public Product(String name, String url, double price, String imageUrl) {
        this.name = name;
        this.url = url;
        this.price = price;
        this.imageUrl = imageUrl;
    }

    public String getName() {
        return name;
    }

    public String getUrl() {
        return url;
    }

    public double getPrice() {
        return price;
    }

    public String getImageUrl() {
        return imageUrl;
    }
}
