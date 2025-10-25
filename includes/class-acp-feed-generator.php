<?php
/**
 * ACP Product Feed Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACP_Feed_Generator {
    
    public function __construct() {
        add_action('init', array($this, 'add_feed_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_feed_query_vars'));
        add_action('template_redirect', array($this, 'handle_feed_request'));
    }
    
    /**
     * Add rewrite rules for product feed
     */
    public function add_feed_rewrite_rules() {
        add_rewrite_rule('^acp/feed/?$', 'index.php?acp_feed=1', 'top');
    }
    
    /**
     * Add query vars
     */
    public function add_feed_query_vars($vars) {
        $vars[] = 'acp_feed';
        return $vars;
    }
    
    /**
     * Handle feed request
     */
    public function handle_feed_request() {
        if (get_query_var('acp_feed')) {
            $this->generate_feed();
            exit;
        }
    }
    
    /**
     * Generate product feed
     */
    public function generate_feed() {
        // Check if feed is enabled
        if (!get_option('acp_enable_feed', true)) {
            wp_die('Product feed is disabled', 'Feed Disabled', array('response' => 403));
        }
        
        // Set headers
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: public, max-age=300'); // 5 minutes cache
        
        try {
            $products = $this->get_products();
            $feed_data = $this->format_feed_data($products);
            
            echo json_encode($feed_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            wp_die('Feed generation error: ' . $e->getMessage(), 'Feed Error', array('response' => 500));
        }
    }
    
    /**
     * Get products for feed
     */
    private function get_products() {
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => get_option('acp_feed_max_products', 1000),
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_stock_status',
                    'value' => 'instock',
                    'compare' => '='
                ),
                array(
                    'key' => '_acp_enable_search',
                    'value' => 'yes',
                    'compare' => '='
                )
            )
        );
        
        // Filter by categories if specified
        $categories = get_option('acp_feed_categories', '');
        if (!empty($categories)) {
            $category_ids = array_map('intval', explode(',', $categories));
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $category_ids,
                    'operator' => 'IN'
                )
            );
        }
        
        $products = get_posts($args);
        $formatted_products = array();
        
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            if (!$product) {
                continue;
            }
            
            $formatted_products[] = $this->format_product($product);
        }
        
        return $formatted_products;
    }
    
    /**
     * Format product data
     */
    private function format_product($product) {
        $images = $this->get_product_images($product);
        $inventory = $this->get_product_inventory($product);
        
        return array(
            'id' => (string) $product->get_id(),
            'title' => $product->get_name(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'sku' => $product->get_sku(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'currency' => get_woocommerce_currency(),
            'images' => $images,
            'inventory' => $inventory,
            'categories' => $this->get_product_categories($product),
            'tags' => $this->get_product_tags($product),
            'attributes' => $this->get_product_attributes($product),
            'variations' => $this->get_product_variations($product),
            'status' => $product->get_status(),
            'featured' => $product->get_featured(),
            'date_created' => $product->get_date_created()->format('c'),
            'date_modified' => $product->get_date_modified()->format('c'),
            'permalink' => get_permalink($product->get_id())
        );
    }
    
    /**
     * Get product images
     */
    private function get_product_images($product) {
        $images = array();
        
        // Featured image
        $featured_image_id = $product->get_image_id();
        if ($featured_image_id) {
            $images[] = array(
                'id' => $featured_image_id,
                'url' => wp_get_attachment_image_url($featured_image_id, 'full'),
                'thumbnail' => wp_get_attachment_image_url($featured_image_id, 'thumbnail'),
                'medium' => wp_get_attachment_image_url($featured_image_id, 'medium'),
                'large' => wp_get_attachment_image_url($featured_image_id, 'large'),
                'alt' => get_post_meta($featured_image_id, '_wp_attachment_image_alt', true)
            );
        }
        
        // Gallery images
        $gallery_ids = $product->get_gallery_image_ids();
        foreach ($gallery_ids as $gallery_id) {
            $images[] = array(
                'id' => $gallery_id,
                'url' => wp_get_attachment_image_url($gallery_id, 'full'),
                'thumbnail' => wp_get_attachment_image_url($gallery_id, 'thumbnail'),
                'medium' => wp_get_attachment_image_url($gallery_id, 'medium'),
                'large' => wp_get_attachment_image_url($gallery_id, 'large'),
                'alt' => get_post_meta($gallery_id, '_wp_attachment_image_alt', true)
            );
        }
        
        return $images;
    }
    
    /**
     * Get product inventory
     */
    private function get_product_inventory($product) {
        return array(
            'manage_stock' => $product->get_manage_stock(),
            'stock_quantity' => $product->get_stock_quantity(),
            'stock_status' => $product->get_stock_status(),
            'backorders' => $product->get_backorders(),
            'sold_individually' => $product->get_sold_individually()
        );
    }
    
    /**
     * Get product categories
     */
    private function get_product_categories($product) {
        $categories = array();
        $category_ids = $product->get_category_ids();
        
        foreach ($category_ids as $category_id) {
            $category = get_term($category_id, 'product_cat');
            if ($category && !is_wp_error($category)) {
                $categories[] = array(
                    'id' => $category->term_id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'parent' => $category->parent
                );
            }
        }
        
        return $categories;
    }
    
    /**
     * Get product tags
     */
    private function get_product_tags($product) {
        $tags = array();
        $tag_ids = $product->get_tag_ids();
        
        foreach ($tag_ids as $tag_id) {
            $tag = get_term($tag_id, 'product_tag');
            if ($tag && !is_wp_error($tag)) {
                $tags[] = array(
                    'id' => $tag->term_id,
                    'name' => $tag->name,
                    'slug' => $tag->slug
                );
            }
        }
        
        return $tags;
    }
    
    /**
     * Get product attributes
     */
    private function get_product_attributes($product) {
        $attributes = array();
        $product_attributes = $product->get_attributes();
        
        foreach ($product_attributes as $attribute) {
            $attributes[] = array(
                'name' => $attribute->get_name(),
                'label' => $attribute->get_label(),
                'options' => $attribute->get_options(),
                'visible' => $attribute->get_visible(),
                'variation' => $attribute->get_variation()
            );
        }
        
        return $attributes;
    }
    
    /**
     * Get product variations (for variable products)
     */
    private function get_product_variations($product) {
        if (!$product->is_type('variable')) {
            return array();
        }
        
        $variations = array();
        $variation_ids = $product->get_children();
        
        foreach ($variation_ids as $variation_id) {
            $variation = wc_get_product($variation_id);
            if ($variation) {
                $variations[] = array(
                    'id' => $variation->get_id(),
                    'sku' => $variation->get_sku(),
                    'price' => $variation->get_price(),
                    'regular_price' => $variation->get_regular_price(),
                    'sale_price' => $variation->get_sale_price(),
                    'stock_quantity' => $variation->get_stock_quantity(),
                    'stock_status' => $variation->get_stock_status(),
                    'attributes' => $variation->get_variation_attributes(),
                    'image' => $this->get_product_images($variation)
                );
            }
        }
        
        return $variations;
    }
    
    /**
     * Format feed data
     */
    private function format_feed_data($products) {
        return array(
            'version' => '1.0',
            'generated_at' => current_time('c'),
            'total_products' => count($products),
            'currency' => get_woocommerce_currency(),
            'store_url' => home_url(),
            'store_name' => get_bloginfo('name'),
            'products' => $products
        );
    }
}