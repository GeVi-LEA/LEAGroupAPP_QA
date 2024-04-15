<?php

class ServicioMovimientoAlmacen
{
    private $idServicio;
    private $cantidad;
    private $almacen;
    private $operacion;
    private $fecha;
    private $cliente_id;
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function getIdServicio()
    {
        return $this->idServicio;
    }

    public function getCantidad()
    {
        return $this->cantidad;
    }

    public function getAlmacen()
    {
        return $this->almacen;
    }

    public function getOperacion()
    {
        return $this->operacion;
    }

    public function setIdServicio($idServicio): void
    {
        $this->idServicio = $idServicio;
    }

    public function setCantidad($cantidad): void
    {
        $this->cantidad = $cantidad;
    }

    public function setAlmacen($almacen): void
    {
        $this->almacen = $almacen;
    }

    public function setOperacion($operacion): void
    {
        $this->operacion = $operacion;
    }

    public function getFecha()
    {
        return $this->fecha;
    }

    public function setFecha($fecha): void
    {
        $this->fecha = $fecha;
    }

    public function getClienteId()
    {
        return $this->cliente_id;
    }

    public function setClienteId($cliente_id): void
    {
        $this->cliente_id = $cliente_id;
    }

    public function save()
    {
        $sql    = 'insert IGNORE  into servicios_movimientos_almacen(
            servicio_id,
            cliente_id,
            almacen_id,
            cantidad,
            operacion,
            fecha,
            lote) values(
     ' . $this->getIdServicio() . '
     , ' . $this->getClienteId() . '
     , ' . $this->getAlmacen() . '
     , ' . $this->getCantidad() . '
     , get_OperacionByEnsacado(' . $this->getIdServicio() . ')
     , NOW()
     , (select lote from servicios_ensacado se where se.id = ' . $this->getIdServicio() . '))';
        $save   = $this->db->query($sql);
        $result = false;
        if ($save) {
            $result = true;
        }
        return $result;
    }

    public function delete()
    {
        $delete = $this->db->query("delete from servicios_clientes where id={$this->getId()}");
        $result = false;
        if ($delete) {
            $result = true;
        }
        return $result;
    }
}
