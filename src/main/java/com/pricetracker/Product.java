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
    @JsonFormat(shape = JsonFormat.Shape.STRING, pattern = "dd.MM.yyyy HH:mm")
    private LocalDateTime createdAt;
    @JsonProperty
    @JsonFormat(shape = JsonFormat.Shape.STRING, pattern = "dd.MM.yyyy HH:mm")
    private LocalDateTime updatedAt;
    @JsonProperty
    private List<PriceHistoryEntry> priceHistory;

    public Product() {
        this.priceHistory = new ArrayList<>();
    }

    public Product(String name, String url, double price, String imageUrl) {
        this.name = name;
        this.url = url;
        this.price = price;
        this.imageUrl = imageUrl;
        this.createdAt = LocalDateTime.now();
        this.updatedAt = LocalDateTime.now();
        this.priceHistory = new ArrayList<>();
        this.priceHistory.add(new PriceHistoryEntry(price, this.updatedAt));
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

    public LocalDateTime getCreatedAt() {
        return createdAt;
    }

    public LocalDateTime getUpdatedAt() {
        return updatedAt;
    }

    public List<PriceHistoryEntry> getPriceHistory() {
        return priceHistory;
    }

    public void setPrice(double price) {
        this.price = price;
        this.updatedAt = LocalDateTime.now();
        this.priceHistory.add(new PriceHistoryEntry(price, this.updatedAt));
    }
}
