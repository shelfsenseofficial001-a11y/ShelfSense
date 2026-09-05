<?php
namespace App\Models;

use App\Core\Database;

/**
 * Single-row config for the 3-way match's price/quantity tolerance, editable
 * by Finance Head. A billed price within EITHER the percent OR the flat
 * amount tolerance of the PO price is accepted (whichever is looser).
 */
class VarianceToleranceSettings
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function get()
    {
        $row = $this->db->query("SELECT * FROM variance_tolerance_settings ORDER BY id DESC LIMIT 1")->fetch();
        if ($row) {
            return $row;
        }
        $this->db->exec("INSERT INTO variance_tolerance_settings (price_tolerance_percent, price_tolerance_amount, quantity_tolerance_percent) VALUES (2.00, 50.00, 0.00)");
        return $this->db->query("SELECT * FROM variance_tolerance_settings ORDER BY id DESC LIMIT 1")->fetch();
    }

    public function update($pricePercent, $priceAmount, $quantityPercent, $userId)
    {
        $current = $this->get();
        $stmt = $this->db->prepare("
            UPDATE variance_tolerance_settings
            SET price_tolerance_percent = ?, price_tolerance_amount = ?, quantity_tolerance_percent = ?, updated_by = ?
            WHERE id = ?
        ");
        return $stmt->execute([$pricePercent, $priceAmount, $quantityPercent, $userId, $current['id']]);
    }
}
