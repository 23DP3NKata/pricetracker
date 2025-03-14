package com.pricetracker;

import java.io.File;
import java.io.IOException;

import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.databind.SerializationFeature;
import com.fasterxml.jackson.datatype.jsr310.JavaTimeModule;

public class FileManager {
    public void writeProductList(ProductList list, String filePath) throws IOException {
        ObjectMapper mapper = new ObjectMapper();
        mapper.registerModule(new JavaTimeModule()); // Регистрация модуля для поддержки Java 8 Date/Time
        mapper.enable(SerializationFeature.INDENT_OUTPUT); // Enable pretty print
        mapper.writeValue(new File(filePath), list);
    }

    public ProductList readProductList(String filename) throws IOException {
        ObjectMapper mapper = new ObjectMapper();
        mapper.registerModule(new JavaTimeModule()); // Регистрация модуля для поддержки Java 8 Date/Time
        return mapper.readValue(new File(filename), ProductList.class);
    }
}
