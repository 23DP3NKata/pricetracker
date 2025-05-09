package com.pricetracker;

import static org.junit.Assert.assertNotNull;
import static org.junit.Assert.assertEquals;

import org.junit.Test;

public class PriceCheckerTest {

    /**
     * Test to verify that fetchProductDetails correctly fetches product details.
     */
    @Test
    public void testFetchProductDetails() {
        String testUrl = "https://www.rdveikals.lv/products/lv/428/689076/sort/5/filter/0_0_0_0/Ryzen-7-9800X3D-100-100001084WOF-Box-procesors.html";

        Product product = PriceChecker.fetchProductDetails(testUrl);

        assertNotNull("Product should not be null", product);
        assertEquals("URL should match the input URL", testUrl, product.getUrl());
        assertNotNull("Product name should not be null", product.getName());
        assertNotNull("Product image URL should not be null", product.getImageUrl());
        assertNotNull("Product shop should not be null", product.getShop());
    }
}