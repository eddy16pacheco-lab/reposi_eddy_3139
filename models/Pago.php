<?php
require_once 'Model.php';

class Pago extends Model {
    protected $table = 'pago';
    protected $primaryKey = 'id_pago';
    
    /**
     * Registrar pago
     */
    public function registrarPago($idVenta, $monto, $tipoPago = 'Efectivo') {
        $sql = "INSERT INTO pago (id_venta, monto_pago, tipo_pago, estado, fecha_pago) 
                VALUES (:id_venta, :monto, :tipo_pago, 'Pagado', NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id_venta' => $idVenta,
            'monto' => $monto,
            'tipo_pago' => $tipoPago
        ]);
    }
    
    /**
     * Pagos pendientes (usando vista)
     */
    public function getPagosPendientes() {
        $sql = "SELECT * FROM vw_report_pagos_pendientes ORDER BY dias_vencidos DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Pagos por cliente
     */
    public function getPagosByCliente($idCliente) {
        $sql = "SELECT p.*, v.fecha_venta, v.tipo_venta
                FROM pago p
                INNER JOIN venta v ON p.id_venta = v.id_venta
                WHERE v.id_cliente = :id_cliente
                ORDER BY p.fecha_pago DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_cliente' => $idCliente]);
        return $stmt->fetchAll();
    }
    
    /**
     * Reporte de pagos diarios
     */
    public function getReportePagosDiarios($fecha = null) {
        $fecha = $fecha ?? date('Y-m-d');
        
        $sql = "SELECT * FROM vw_report_pagos_diarios WHERE fecha = :fecha";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['fecha' => $fecha]);
        return $stmt->fetch();
    }
    
    /**
     * Reporte de pagos mensuales
     */
    public function getReportePagosMensuales($anio = null) {
        $anio = $anio ?? date('Y');
        
        $sql = "SELECT * FROM vw_report_pagos_mensuales WHERE año = :anio ORDER BY mes DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['anio' => $anio]);
        return $stmt->fetchAll();
    }
    
    /**
     * Marcar pago como anulado
     */
    public function anularPago($idPago) {
        $sql = "UPDATE pago SET estado = 'Anulado' WHERE id_pago = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $idPago]);
    }
}