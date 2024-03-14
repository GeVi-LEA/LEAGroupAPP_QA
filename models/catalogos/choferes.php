<?php

class CatChoferes
{
    private $id;
    private $cat_transp_id;
    private $nombres;
    private $apellidos;
    private $num_licencia;
    private $vigencia;
    private $ine;
    private $comentarios;
    private $fecha_alta;
    private $usuario_alta;
    private $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    function getId()
    {
        return $this->id;
    }

    function getCatTranspId()
    {
        return $this->cat_transp_id;
    }

    function getNombres()
    {
        return $this->nombres;
    }

    function getApellidos()
    {
        return $this->apellidos;
    }

    function getNumLicencia()
    {
        return $this->num_licencia;
    }

    function getVigencia()
    {
        return $this->vigencia;
    }

    function getIne()
    {
        return $this->ine;
    }

    function getComentarios()
    {
        return $this->comentarios;
    }

    function getFecha_Alta()
    {
        return $this->fecha_alta;
    }

    function getUsuario_Alta()
    {
        return $this->usuario_alta;
    }

    function setId($id): void
    {
        $this->id = $id;
    }

    function setCatTranspId($cat_transp_id): void
    {
        $this->cat_transp_id = $cat_transp_id;
    }

    function setNombres($nombres): void
    {
        $this->nombres = $this->db->real_escape_string(UtilsHelp::fixString($nombres));
    }

    function setApellidos($apellidos): void
    {
        $this->apellidos = $this->db->real_escape_string(UtilsHelp::fixString($apellidos));
    }

    function setNumLicencia($num_lincencia): void
    {
        $this->num_lincencia = $this->db->real_escape_string(UtilsHelp::fixString($num_lincencia));
    }

    function setVigencia($vigencia): void
    {
        $this->vigencia = $this->db->real_escape_string(UtilsHelp::fixString($vigencia));
    }

    function setIne($ine): void
    {
        $this->ine = $this->db->real_escape_string(UtilsHelp::fixString($ine));
    }

    function setComentarios($comentarios): void
    {
        $this->comentarios = $this->db->real_escape_string(UtilsHelp::fixString($comentarios));
    }

    public function save()
    {
        $sql    = "insert into catalogo_choferes values
        (
            null
            , '{$this->getCatTranspId()}'
            , '{$this->getNombres()}'
            , '{$this->getApellidos()}'
            , '{$this->getNumLicencia()}'
            , '{$this->getVigencia()}'
            , '{$this->getIne()}'
            , '{$this->getComentarios()}'
            , now()
            , {$_SESSION['usuario']->id}
        )";
        $save   = $this->db->query($sql);
        $result = false;
        if ($save) {
            $result = true;
        }
        return $result;
    }

    public function edit()
    {
        $sql    = "update catalogo_transportistas_clientes set 
              cat_transp_id = '{$this->getCatTranspId()}' 
            , nombres = '{$this->getNombres()}' 
            , apellidos = '{$this->getApellidos()}' 
            , num_licencia = '{$this->getNumLicencia()}' 
            , vigencia = '{$this->getVigencia()}' 
            , ine = '{$this->getIne()}' 
            , comentarios= '{$this->getComentarios()}'
         where id = {$this->getId()}";
        $save   = $this->db->query($sql);
        $result = false;
        if ($save) {
            $result = true;
        }
        return $result;
    }

    public function getAll($sql = '')
    {
        $result = array();
        $query  = "
            SELECT 
         chof.id as chof_id 
       , chof.nombres as chof_nombres
       , chof.apellidos as chof_apellidos 
       , chof.cat_transp_id as chof_trans_id
       , chof.is_lea as chof_is_lea
       , chof.num_licencia as chof_licencia
       , chof.vigencia as chof_vigencia
       , chof.comentarios as chof_comentarios
       , chof.ine as chof_ine
       , if(chof.is_lea like '%S%', (select p.nombre from catalogo_proveedores p where p.id = chof.cat_transp_id), 
        ( select ct.nombre from catalogo_transportistas_clientes ct where ct.id = chof.cat_transp_id)) as transportista
        FROM catalogo_choferes_transportistas chof";

        if ($sql != '') {
            $query .= $sql;
        }
        $query .= ' order by chof.nombres';
        // print_r('<pre>');
        // print_r($query);
        // print_r('</pre>');
        $Transportes = $this->db->query($query);
        while ($t = $Transportes->fetch_object()) {
            array_push($result, $t);
        }
        return $result;
    }

    public function getById($id)
    {
        $tipoTransportes = $this->db->query("select * from catalogo_choferes where id= {$id}");
        return $tipoTransportes->fetch_object();
    }

    public function getChoferesByTransporte($transp_id)
    {
        $sql    = " where cat_trans.id = {$transp_id} ";
        $result = $this->getAll($sql);
        return $result;
    }

    public function delete()
    {
        $delete = $this->db->query("delete from catalogo_choferes where id={$this->id}");
        $result = false;
        if ($delete) {
            $result = true;
        }
        return $result;
    }

    public function getByNombres($nombres)
    {
        $tipoTransportes = $this->db->query("select * from catalogo_choferes where nombres = '{$nombres}'");
        return $tipoTransportes->fetch_object();
    }
}