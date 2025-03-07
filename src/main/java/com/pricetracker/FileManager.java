package com.pricetracker;

import java.io.File;
import java.io.IOException;

import com.fasterxml.jackson.core.JsonGenerationException;
import com.fasterxml.jackson.core.JsonParseException;
import com.fasterxml.jackson.databind.JsonMappingException;
import com.fasterxml.jackson.databind.ObjectMapper;

public class FileManager {
    public void writeProductList(ProductList list) throws IOException {
        ObjectMapper mapper = new ObjectMapper();
        mapper.writeValue(new File(list.getName() + ".json"), list);
    }
    
    public ProductList readProductList(String filename) throws IOException {
        ObjectMapper mapper = new ObjectMapper();
        return mapper.readValue(new File(filename), ProductList.class);
    }    
}
