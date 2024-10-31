<?php

namespace Premmerce\ProductBundles;

/**
 * Class ProductBundlesModel
 * @package Premmerce\ProductBundles
 */
class ProductBundlesModel
{
    /**
     * @var \wpdb
     */
    private $wpdb;

    /**
     * @var array
     */
    private $tables = array();

    /**
     * ProductBundlesModel constructor.
     */
    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;

        $this->tables = array(
            'bundles'          => $this->wpdb->prefix . 'premmerce_bundles',
            'bundles_products' => $this->wpdb->prefix . 'premmerce_bundles_products',
        );
    }

    /**
     * Create row in DB
     *
     * @param array $data
     * @param string $table
     */
    public function createItem($data, $table)
    {
        $this->wpdb->insert($this->tables[$table], $data);
    }

    /**
     * Update item in DB
     *
     * @param string $table
     * @param array $data
     * @param int $id
     */
    public function updateItem($table, $data, $id)
    {
        $this->wpdb->update(
            $this->tables[$table],
            $data,
            array(
                'id' => $id,
            )
        );
    }

    /**
     * Get bundles from DB
     *
     * @return array
     */
    public function getBundles()
    {
        $sql = '
            SELECT *
            FROM ' . $this->tables['bundles'] . '
        ';

        return $this->wpdb->get_results($sql);
    }

    /**
     * Get bundles by id from DB
     *
     * @param $id
     *
     * @return array
     */
    public function getBundleById($id)
    {
        $sql = '
	        SELECT *
	        FROM ' . $this->tables['bundles'] . '
	        
	        WHERE id = %d
	    ';

        $sql = $this->wpdb->prepare($sql, array($id));

        return $this->wpdb->get_row($sql);
    }

    /**
     * Get bundle products by bundle id from DB
     *
     * @param $id
     *
     * @return array
     */
    public function getBundleProductsByBundleId($id)
    {
        $sql = '
            SELECT *
            FROM ' . $this->tables['bundles_products'] . '
            
            WHERE bundle_id = %d
        ';

        $sql = $this->wpdb->prepare($sql, array($id));

        return $this->wpdb->get_results($sql);
    }

    /**
     * Get array of bundle product ids
     *
     * @param $id
     *
     * @return array
     */
    public function getBundleProductsIds($id)
    {
        $sql = '
            SELECT product_id
            FROM ' . $this->tables['bundles_products'] . '
            
            WHERE bundle_id = %d
        ';

        $sql = $this->wpdb->prepare($sql, array($id));

        return $this->wpdb->get_col($sql);
    }

    /**
     * Get bundle products by product id from DB
     *
     * @param $id
     *
     * @return array
     */
    public function getBundleProductsByProductId($id)
    {
        $sql = '
            SELECT *
            FROM ' . $this->tables['bundles_products'] . '
            
            WHERE product_id = %d
        ';

        $sql = $this->wpdb->prepare($sql, array($id));

        return $this->wpdb->get_results($sql);
    }

    /**
     * Get bundle by product id from DB
     *
     * @param $id
     *
     * @return array
     */
    public function getBundleIdByProductId($id)
    {
        $sql = '
            SELECT `b`.`id`
            FROM `' . $this->tables['bundles'] . '` AS b
            LEFT JOIN `' . $this->tables['bundles_products'] . '` AS bp ON `bp`.`bundle_id` = `b`.`id`
            WHERE `b`.`main_product_id` = %d OR `bp`.`product_id` = %d;
        ';

        $sql = $this->wpdb->prepare($sql, array($id, $id));

        return $this->wpdb->get_var($sql);
    }

    /**
     * Get bundles by main product id from DB
     *
     * @param $id
     *
     * @return array
     */
    public function getBundlesByMainProduct($id)
    {
        $sql = '
            SELECT *
            FROM ' . $this->tables['bundles'] . '
            
            WHERE main_product_id = %d
        ';

        $sql = $this->wpdb->prepare($sql, array($id));

        return $this->wpdb->get_results($sql);
    }

    /**
     * Get bundles by main product ids from DB
     *
     * @param array $ids
     *
     * @return array
     */
    public function getBundlesByMainProducts($ids)
    {
        $in = implode(',', $ids);

        $sql = '
            SELECT *
            FROM ' . $this->tables['bundles'] . '
            
            WHERE main_product_id IN (' . $in . ')
        ';

        return $this->wpdb->get_results($sql);
    }

    /**
     * Delete item in DB
     *
     * @param string $table
     * @param array $conditions
     */
    public function deleteItem($table, $conditions)
    {
        $this->wpdb->delete($this->tables[$table], $conditions);
    }

    /**
     * Delete bundle by id
     *
     * @param int $id
     */
    public function deleteBundle($id)
    {
        $this->deleteItem('bundles', array('id' => $id));
        $this->deleteItem('bundles_products', array('bundle_id' => $id));
    }

    /**
     * Get last insert ID in DB
     * @return int
     */
    public function getLastInsertId()
    {
        return $this->wpdb->insert_id;
    }

    /**
     * Check bundle visible by id
     *
     * @param $id
     *
     * @return array
     */
    public function checkBundleVisible($id)
    {
        $errors = array();

        $bundle = $this->getBundleById($id);

        $bundleProductsIds = $this->getBundleProductsIds($id);
        array_unshift($bundleProductsIds, $bundle->main_product_id);

        foreach ($bundleProductsIds as $id) {
            $errors = $this->checkProductAvailable($id, $errors);
        }

        return array(
            'status' => empty($errors),
            'errors' => $errors,
        );
    }

    /**
     * Check available product for render bundle
     *
     * @param int $productId
     * @param array $errors
     *
     * @return array
     *
     */
    private function checkProductAvailable($productId, $errors)
    {
        $product = wc_get_product($productId);

        if ($product) {
            $isVariation = $product->get_type() == 'variation';

            $parentProduct  = null;
            $parentStatus   = '';
            $parentPassword = '';

            if ($isVariation) {
                $parentProduct  = wc_get_product($product->get_parent_id());
                $parentStatus   = $parentProduct->get_status();
                $parentPassword = post_password_required($product->get_parent_id());
            }

            $status   = $product->get_status();
            $password = post_password_required($productId);

            $productUrl = '<a href="' . get_edit_post_link($isVariation ? $product->get_parent_id() : $productId) . '">' . $product->get_name() . '</a> ';

            if ($status != 'publish' || ($isVariation && $parentStatus != 'publish')) {
                if ($isVariation && $parentStatus == 'private') {
                    if (! user_can(get_current_user_id(), 'read_private_products')) {
                        $errors[] = $productUrl . __('is not available', ProductBundlesPlugin::DOMAIN);
                    }
                } else {
                    $errors[] = $productUrl . __('is not available', ProductBundlesPlugin::DOMAIN);
                }
            }

            if ($password || ($isVariation && $parentPassword)) {
                $errors[] = $productUrl . __('is password protected', ProductBundlesPlugin::DOMAIN);
            }

            if (! $product->get_regular_price()) {
                $errors[] = $productUrl . __('regular price is empty', ProductBundlesPlugin::DOMAIN);
            }

            if ($product->managing_stock() && ! $product->is_in_stock()) {
                $errors[] = $productUrl . __('out of stock', ProductBundlesPlugin::DOMAIN);
            }
        }

        return $errors;
    }

    /**
     * Create bundles table in DB
     */
    public function createBundlesTable()
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS ' . $this->tables['bundles'] . ' (
                id INT(11) NOT NULL AUTO_INCREMENT,
                main_product_id INT(11) NOT NULL,
                active TINYINT(4) NOT NULL,
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET ' . $this->wpdb->charset . ' COLLATE ' . $this->wpdb->collate;

        $this->wpdb->query($sql);
    }

    /**
     * Create product bundles table in DB
     */
    public function createBundleProductsTable()
    {
        $sql = '            
            CREATE TABLE IF NOT EXISTS ' . $this->tables['bundles_products'] . ' (
                id INT(11) NOT NULL AUTO_INCREMENT,
                bundle_id INT(11) NOT NULL,
                product_id INT(11) NOT NULL,
                discount INT(11) NOT NULL,
                PRIMARY KEY (id)
            ) DEFAULT CHARACTER SET ' . $this->wpdb->charset . ' COLLATE ' . $this->wpdb->collate;

        $this->wpdb->query($sql);
    }

    /**
     * Search products by string and remove exceptions
     *
     * @param string $search
     * @param array $exceptions
     * @param string $parentId
     *
     * @return array
     */
    public function searchProductsByString($search, $exceptions, $parentId = '')
    {
        $where = 'AND p.post_status NOT IN ("trash", "auto-draft")';


        $bindSearch = array_fill(0, 3, '%' . $search . '%');
        $likeQuery  = 'AND (p.post_title LIKE "%s" OR pm.meta_value LIKE "%s" OR p.ID LIKE "%s")';
        $where .= $this->wpdb->prepare($likeQuery, $bindSearch);

        if (! empty($exceptions)) {
            $exceptPlaceholders = array_fill(0, count($exceptions), '%d');
            $exceptQuery        = sprintf('AND p.ID NOT IN (%s)', implode(',', $exceptPlaceholders));
            $where              .= $this->wpdb->prepare($exceptQuery, $exceptions);
        }

        if (! empty($parentId)) {
            $parentQuery = 'AND p.post_parent = %d';
            $where       .= $this->wpdb->prepare($parentQuery, $parentId);
        }

        $prefix = $this->wpdb->prefix;

        $selectFrom = "SELECT p.ID, p.post_title FROM {$prefix}posts p";
        $joinSku    = "LEFT JOIN {$prefix}postmeta pm ON p.ID = post_id AND pm.meta_key = '_sku'";

        $sql[] = "(";
        $sql[] = $selectFrom;
        $sql[] = $joinSku;
        $sql[] = "JOIN {$prefix}term_relationships tr ON tr.object_id = p.ID";
        $sql[] = "JOIN {$prefix}term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'product_type'";
        $sql[] = "JOIN {$prefix}terms t ON t.term_id = tt.term_id";
        $sql[] = "WHERE p.post_type = 'product'";
        $sql[] = "AND t.slug = 'simple'";
        $sql[] = $where;
        $sql[] = ")";

        $sql[] = "UNION";

        $sql[] = "(";
        $sql[] = $selectFrom;
        $sql[] = $joinSku;
        $sql[] = "WHERE p.post_type = 'product_variation'";
        $sql[] = $where;
        $sql[] = ')';

        $sql[] = 'LIMIT 10';

        $products = $this->wpdb->get_results(implode(' ', $sql), ARRAY_A);
        if (! $products) {
            $products = array();
        }

        return $products;
    }
}
