package com.pricetracker;

import com.fasterxml.jackson.annotation.JsonFormat;
import com.fasterxml.jackson.annotation.JsonProperty;

import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;

public class Product {
    @JsonProperty
    private String name;
    @JsonProperty
    private String url;
    @JsonProperty
    private double price;
    @JsonProperty
    private String imageUrl;
    @JsonProperty
    private String shop;
    @JsonProperty
    @JsonFormat(shape = JsonFormat.Shape.STRING, pattern = "dd.MM.yyyy HH:mm")
    private LocalDateTime createdAt;
    @JsonProperty
    @JsonFormat(shape = JsonFormat.Shape.STRING, pattern = "dd.MM.yyyy HH:mm")
    private LocalDateTime updatedAt;
    @JsonProperty
    private List<PriceHistoryEntry> priceHistory = new ArrayList<>();

    public Product() {
    }

    public Product(String name, String url, double price, String imageUrl, String shop) {
        this.name = name;
        this.url = url;
        this.price = price;
        this.imageUrl = imageUrl;
        this.shop = shop;
        this.createdAt = LocalDateTime.now();
        this.updatedAt = LocalDateTime.now();
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

    public String getShop() {
        return shop;
    }

    public LocalDateTime getCreatedAt() {
        return createdAt;
    }

    public LocalDateTime getUpdatedAt() {
        return updatedAt;
    }

    public List<PriceHistoryEntry> getPriceHistory() {
        return priceHistory;
    }

    public void setName(String name) {
        this.name = name;
    }

    public void setPrice(double price) {
        this.price = price;
    }

    public void setImageUrl(String imageUrl) {
        this.imageUrl = imageUrl;
    }

    public void setCreatedAt(LocalDateTime createdAt) {
        this.createdAt = createdAt;
    }

    public void setUpdatedAt(LocalDateTime updatedAt) {
        this.updatedAt = updatedAt;
    }

    public void addPriceHistoryEntry(PriceHistoryEntry entry) {
        this.priceHistory.add(entry);
    }
}
