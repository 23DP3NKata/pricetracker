package com.pricetracker;

import com.fasterxml.jackson.annotation.JsonFormat;
import com.fasterxml.jackson.annotation.JsonProperty;

import java.time.LocalDateTime;

public class PriceHistoryEntry {
    @JsonProperty
    private double price;
    @JsonProperty
    @JsonFormat(shape = JsonFormat.Shape.STRING, pattern = "dd.MM.yyyy HH:mm")
    private LocalDateTime date;

    public PriceHistoryEntry() {
    }

    public PriceHistoryEntry(double price, LocalDateTime date) {
        this.price = price;
        this.date = date;
    }

    public double getPrice() {
        return price;
    }

    public LocalDateTime getDate() {
        return date;
    }
}
