<?php

require_once utils_root . 'utilsHelp.php';
require_once config_root . 'db.php';
require_once models_root . 'catalogos/cliente.php';
require_once models_root . 'catalogos/producto_resina_liquido.php';
require_once models_root . 'catalogos/tipo_empaque.php';
require_once models_root . 'catalogos/tipo_transporte.php';
require_once models_root . 'catalogos/servicio.php';
require_once models_root . 'catalogos/unidad.php';
require_once models_root . 'catalogos/documento_norma.php';
require_once models_root . 'servicios/servicio_cliente.php';
require_once models_root . 'servicios/bascula.php';
require_once models_root . 'servicios/servicio_entrada.php';
require_once models_root . 'servicios/servicio_ensacado.php';
require_once models_root . 'servicios/servicio_movimiento_almacen.php';

class serviciosController
{
        public function index()
        {
                Utils::noLoggin();
                require_once views_root . 'servicios/servicios.php';
        }

        public function ensacado()
        {
                Utils::noLoggin();
                $idEst = null;

                $trenArray = Utils::isTren();
                $arrayIdsTr = array();
                foreach ($trenArray as $tr) {
                        array_push($arrayIdsTr, $tr->id);
                }

                $ensacado = new ServicioEntrada();
                if (isset($_GET['idEst'])) {
                        $idEst = (int) $_GET['idEst'];
                }
                $servicios = $ensacado->getByEstatusId($idEst);

                require_once views_root . 'servicios/lista_ensacado.php';
        }

        public function generarEnsacado()
        {
                Utils::noLoggin();
                if (isset($_GET['id']) && $_GET['id'] != '') {
                        $e = new ServicioEntrada();
                        $id = $_GET['id'];
                        $e->setId($id);
                        $ensacado = $e->getById();

                        if ($ensacado['ticket'] != 0 || $ensacado['ticket'] != '' && $ensacado['doc_ticket'] != '') {
                                $trenArray = Utils::isTren();
                                $arrayIdsTr = array();
                                foreach ($trenArray as $tr) {
                                        array_push($arrayIdsTr, $tr->id);
                                }

                                $isTren = in_array($ensacado['tipo_transporte_id'], $arrayIdsTr);
                                $bascula = new Bascula();
                                $base = $isTren ? BD_FERRO : BD_CAMIONERA;
                                $bascula->setBase($base);
                                $bascula->setFolio($ensacado['ticket']);
                                $pesaje = $bascula->getPesos();

                                $pesoNeto = $pesaje['EntPesoB'] - $pesaje['EntPesoT'];
                        }
                        $cantidadTotal = null;
                        $isDescarga = true;
                        $servicios = null;
                        $tolerable = null;
                        $diferenciaReal = null;

                        if (!empty($ensacado['servicio'])) {
                                foreach ($ensacado['servicio'] as $s) {
                                        $cantidadTotal += intval($s['cantidad']);
                                        if ($s['claveServ'] == 'CARGA') {
                                                $isDescarga = false;
                                        }
                                }
                                if ($isDescarga) {
                                        $serCli = new ServicioCliente();
                                        $serCli->setClienteId($ensacado['cliente_id']);
                                        $servicios = $serCli->getServicioByCliente();
                                } else {
                                        $serv = new Servicio();
                                        $servicios = $serv->getServiciosSalidas();
                                }
                        } else {
                                $serCli = new ServicioCliente();
                                $serCli->setClienteId($ensacado['cliente_id']);
                                $serviciosCliente = $serCli->getServicioByCliente();

                                $serv = new Servicio();
                                $serviciosSalidas = $serv->getServiciosSalidas();

                                $servicios = array_merge($serviciosCliente, $serviciosSalidas);
                        }

                        $tiempoTranscurrido = intval($ensacado['tiempoTranscurrido']);
                        if ($tiempoTranscurrido > 0) {
                                $tiempo = UtilsHelp::tiempoTranscurrido($tiempoTranscurrido);
                        }
                }
                $trenArray = Utils::isTren();
                $arrayIdsTr = array();
                foreach ($trenArray as $tr) {
                        array_push($arrayIdsTr, $tr->id);
                }

                $isTren = in_array($ensacado['tipo_transporte_id'], $arrayIdsTr);

                $cliente = new Cliente();
                $clientes = $cliente->getClienteConServiciosEnsacado();

                $producto = new ProductoResinaLiquido();
                $productos = $producto->getAll();

                $tipoTrans = new TipoTransporte();
                $transportes = $tipoTrans->getAll();

                $tipo = new TipoEmpaque();
                $tiposEmpaques = $tipo->getAll();

                $tipoTrans = new TipoTransporte();
                if ($isTren) {
                        $transportes = $tipoTrans->isTren();
                } else {
                        $transportes = $tipoTrans->isCamion();
                }
                require_once views_root . 'servicios/ensacado.php';
        }

        public function clientesServicios()
        {
                Utils::noLoggin();

                $cliente = new Cliente();
                $clientes = $cliente->getAll();

                $servicio = new Servicio();
                $servicios = $servicio->getAll();

                $unidad = new Unidad();
                $unidades = $unidad->getAll();

                $tipo = new TipoEmpaque();
                $tiposEmpaques = $tipo->getAll();

                $servicioCliente = new ServicioCliente();
                $serviciosClientes = $servicioCliente->getAllClientes();

                $titulos = array();

                $valores = array();
                $i = 0;

                if (!empty($serviciosClientes)) {
                        foreach ($serviciosClientes[0] as $p => $val) {
                                array_push($titulos, $p);
                        }

                        foreach ($serviciosClientes as $s) {
                                $valores[$i] = array();

                                foreach ($s as $p => $val) {
                                        $valores[$i][] = $val;
                                }
                                $i++;
                        }
                }
                require_once views_root . 'servicios/servicio_cliente.php';
        }

        public function saveServicioCliente()
        {
                Utils::noLoggin();
                $result = [];
                $id = isset($_POST['id']) && $_POST['id'] != '' ? $_POST['id'] : false;
                $idCliente = isset($_POST['cliente']) && $_POST['cliente'] != '' ? $_POST['cliente'] : false;
                $idServicio = isset($_POST['servicio']) && $_POST['servicio'] != '' ? $_POST['servicio'] : false;
                $idEmpaque = isset($_POST['empaque']) && $_POST['empaque'] != '' ? $_POST['empaque'] : 'null';
                $idUnidad = isset($_POST['unidad']) && $_POST['unidad'] != '' ? $_POST['unidad'] : false;
                $costo = isset($_POST['costo']) && $_POST['costo'] != '' ? Utils::quitarComas($_POST['costo']) : false;
                $moneda = isset($_POST['moneda']) && $_POST['moneda'] != '' ? $_POST['moneda'] : false;
                $dias = isset($_POST['dias']) && $_POST['dias'] != '' ? $_POST['dias'] : 'null';

                if ($idCliente && $idServicio && $idUnidad && $moneda) {
                        $servicio = new ServicioCliente();
                        $servicio->setClienteId($idCliente);
                        $servicio->setServicioId($idServicio);
                        $servicio->setTipoEmpaqueId($idEmpaque);
                        $servicio->setUnidadId($idUnidad);
                        $servicio->setCosto(floatval($costo));
                        $servicio->setMoneda($moneda);
                        $servicio->setDias($dias);
                        if (!$id) {
                                if ($servicio->getDuplicado()) {
                                        $result = [
                                                'error' => false,
                                                'mensaje' => 'Servicio para el cliente ya existe.'
                                        ];
                                } else {
                                        $r = $servicio->save();
                                        if ($r) {
                                                $result = [
                                                        'error' => true,
                                                        'mensaje' => 'Se guardo correctamente.'
                                                ];
                                        } else {
                                                $result = [
                                                        'error' => false,
                                                        'mensaje' => 'Ocurrio un error, no se pudo guardar.'
                                                ];
                                        }
                                }
                        } else {
                                $servicio->setId($id);
                                $r = $servicio->edit();

                                if ($r) {
                                        $result = [
                                                'error' => true,
                                                'mensaje' => 'Se edito correctamente.'
                                        ];
                                } else {
                                        $result = [
                                                'error' => false,
                                                'mensaje' => 'Ocurrio un error, no se pudo editar.'
                                        ];
                                }
                        }
                } else {
                        $result = [
                                'error' => false,
                                'mensaje' => 'Ocurrio un errro, revise los datos.'
                        ];
                }
                print_r(json_encode($result));
        }

        public function servicioClienteById()
        {
                $id = isset($_POST['id']) && $_POST['id'] != '' ? $_POST['id'] : false;

                if ($id) {
                        $servicio = new ServicioCliente();
                        $servicio->setId($id);
                        $result = $servicio->getById();
                }
                print_r(json_encode($result));
        }

        public function deleteServicioCliente()
        {
                $id = isset($_POST['id']) && $_POST['id'] != '' ? $_POST['id'] : false;

                if ($id) {
                        $result = [];
                        $servicio = new ServicioCliente();
                        $servicio->setId($id);
                        $r = $servicio->delete();

                        if ($r) {
                                $result = [
                                        'error' => true,
                                        'mensaje' => 'Se elimino correctamente.'
                                ];
                        } else {
                                $result = [
                                        'error' => false,
                                        'mensaje' => 'No se pudo eliminar.'
                                ];
                        }
                }
                print_r(json_encode($result));
        }

        public function getPesos()
        {
                $folio = isset($_POST['id']) && $_POST['id'] != '' ? $_POST['id'] : null;
                $ferrotolva = isset($_POST['ferrotolva']) ? $_POST['ferrotolva'] : null;
                if ($folio != null && $ferrotolva != null) {
                        $base = BD_CAMIONERA;
                        if ($ferrotolva == 1) {
                                $base = BD_FERRO;
                        }

                        $bascula = new Bascula();
                        $bascula->setBase($base);
                        $bascula->setFolio($folio);
                        $peso = $bascula->getPesos();
                        return print_r(json_encode($peso));
                }
                return false;
        }

        public function guardarEnsacado()
        {
                $result = [];
                $id = isset($_POST['id']) && $_POST['id'] != '' ? $_POST['id'] : false;
                $numeroFerro = isset($_POST['numeroUnidad']) && $_POST['numeroUnidad'] != '' ? $_POST['numeroUnidad'] : false;
                $idCliente = $_POST['cliente'];
                $productoId = isset($_POST['producto']) && $_POST['producto'] != '' ? $_POST['producto'] : null;

                $transportista = isset($_POST['transportista']) && $_POST['transportista'] != '' ? $_POST['transportista'] : null;
                $chofer = isset($_POST['chofer']) && $_POST['chofer'] != '' ? $_POST['chofer'] : null;
                $orden = isset($_POST['orden']) && $_POST['orden'] != '' ? $_POST['orden'] : null;
                $lote = isset($_POST['lote']) && $_POST['lote'] != '' ? $_POST['lote'] : null;
                $alias = isset($_POST['alias']) && $_POST['alias'] != '' ? $_POST['alias'] : null;
                $transporte = isset($_POST['transporte']) && $_POST['transporte'] != '' ? $_POST['transporte'] : null;
                $placa1 = isset($_POST['placa1']) && $_POST['placa1'] != '' ? $_POST['placa1'] : null;
                $placa2 = isset($_POST['placa2']) && $_POST['placa2'] != '' ? $_POST['placa2'] : null;
                $pesoCliente = isset($_POST['pesoCliente']) && $_POST['pesoCliente'] != '' ? $_POST['pesoCliente'] : null;
                $pesoTara = isset($_POST['tara']) && $_POST['tara'] != '' ? $_POST['tara'] : null;
                $ticket = isset($_POST['ticket']) && $_POST['ticket'] != '' ? $_POST['ticket'] : null;
                $pesoTeorico = isset($_POST['pesoTeorico']) && $_POST['pesoTeorico'] != '' ? $_POST['pesoTeorico'] : null;
                $pesoBruto = isset($_POST['pesoBruto']) && $_POST['pesoBruto'] != '' ? $_POST['pesoBruto'] : null;
                $pesoNeto = isset($_POST['pesoNeto']) && $_POST['pesoNeto'] != '' ? $_POST['pesoNeto'] : null;
                $archivoTicket = isset($_POST['archivoTicket']) && $_POST['archivoTicket'] != '' ? $_POST['archivoTicket'] : null;
                $archivoBill = isset($_POST['archivoBill']) && $_POST['archivoBill'] != '' ? $_POST['archivoBill'] : null;
                $sello1 = isset($_POST['sello1']) && $_POST['sello1'] != '' ? $_POST['sello1'] : null;
                $sello2 = isset($_POST['sello2']) && $_POST['sello2'] != '' ? $_POST['sello2'] : null;
                $sello3 = isset($_POST['sello3']) && $_POST['sello3'] != '' ? $_POST['sello3'] : null;
                $observaciones = isset($_POST['observaciones']) && $_POST['observaciones'] != '' ? $_POST['observaciones'] : null;
                $estatus = 1;

                if ($numeroFerro) {
                        $ensacado = new ServicioEntrada();
                        $ensacado->setId($id);
                        $ensacado->setNumUnidad($numeroFerro);
                        $ensacado->setClienteId($idCliente);
                        $ensacado->setEstatusId($estatus);
                        $ensacado->setPesoCliente($pesoCliente != null ? Utils::stringToFloat($pesoCliente) : 'null');
                        $ensacado->setPesoTara($pesoTara != null ? Utils::stringToFloat($pesoTara) : 'null');
                        $ensacado->setPesoTeorico($pesoTeorico != null ? Utils::stringToFloat($pesoTeorico) : 'null');
                        $ensacado->setPesoBruto($pesoBruto != null ? Utils::stringToFloat($pesoBruto) : 'null');
                        $ensacado->setPesoNeto($pesoNeto != null ? Utils::stringToFloat($pesoNeto) : 'null');
                        $ensacado->setTicket($ticket != null ? $ticket : 'null');
                        $ensacado->setTransportista($transportista);
                        $ensacado->setChofer($chofer);
                        $ensacado->setTipoTransporteId($transporte);
                        $ensacado->setPlaca1($placa1);
                        $ensacado->setPlaca2($placa2);
                        $ensacado->setObservaciones($observaciones);
                        $ensacado->setSello1($sello1);
                        $ensacado->setSello2($sello2);
                        $ensacado->setSello3($sello3);
                        $ensacado->setEstatusId($estatus);

                        $f = $ensacado->unidadRegistrada();
                        if ($f->num_rows >= 1) {
                                $result = [
                                        'error' => false,
                                        'mensaje' => 'Unidad ya registrada.'
                                ];
                        } else {
                                if (!$id) {
                                        $r = $ensacado->save();
                                        if ($r) {
                                                $result = [
                                                        'error' => true,
                                                        'mensaje' => 'Se guardo correctamente.'
                                                ];
                                        } else {
                                                $result = [
                                                        'error' => false,
                                                        'mensaje' => 'Ocurrio un error, no se pudo guardar.'
                                                ];
                                        }
                                } else {
                                        $e = $ensacado->getById();
                                        $nomUnidad = $ensacado->getNumUnidad();

                                        if ($nomUnidad != $e['numUnidad']) {
                                                if (!is_dir('views/servicios/uploads/' . $nomUnidad)) {
                                                        mkdir('views/servicios/uploads/' . $nomUnidad, 0777, true);
                                                }
                                                $archivos = glob('views/servicios/uploads/' . $e['numUnidad'] . '/*');

                                                if (!empty($archivos)) {
                                                        foreach ($archivos as $arch) {
                                                                $a = substr($arch, strrpos($arch, '/') + 1);
                                                                copy($arch, 'views/servicios/uploads/' . $nomUnidad . '/' . $a);
                                                                unlink($arch);
                                                        }
                                                }

                                                if (file_exists('views/servicios/uploads/' . $nomUnidad . '/' . $e['doc_remision'])) {
                                                        $extension = explode('.', $e['doc_remision'])[1];
                                                        $filename = 'BL-' . $nomUnidad . '.' . $extension;
                                                        rename('views/servicios/uploads/' . $nomUnidad . '/' . $e['doc_remision'], 'views/servicios/uploads/' . $nomUnidad . '/' . $filename);

                                                        rmdir('views/servicios/uploads/' . $e['numUnidad']);

                                                        $ensacado->setDocRemision($filename);
                                                }
                                        } else {
                                                if (isset($_FILES['documentoBill']) && $_FILES['documentoBill']['size'] > 0) {
                                                        $file = $_FILES['documentoBill'];
                                                        $mimetype = $file['type'];
                                                        $extension = explode('.', $file['name'])[1];
                                                        $filename = 'BL-' . $nomUnidad . '.' . $extension;

                                                        if ($mimetype == 'image/jpeg' || $mimetype == 'image/jpg' || $mimetype == 'image/png' || $mimetype == 'application/pdf') {
                                                                if (!is_dir('views/servicios/uploads/' . $nomUnidad)) {
                                                                        mkdir('views/servicios/uploads/' . $nomUnidad, 0777, true);
                                                                }
                                                                if (file_exists('views/servicios/uploads/' . $nomUnidad . '/' . $filename)) {
                                                                        unlink('views/servicios/uploads/' . $nomUnidad . '/' . $filename);
                                                                }
                                                                move_uploaded_file($file['tmp_name'], 'views/servicios/uploads/' . $nomUnidad . '/' . $filename);
                                                                $ensacado->setDocRemision($filename);
                                                        }
                                                } else {
                                                        $ensacado->setDocRemision($archivoBill);
                                                }
                                        }
                                        if (isset($_FILES['documentoTicket']) && $_FILES['documentoTicket']['size'] > 0) {
                                                $file = $_FILES['documentoTicket'];
                                                $mimetype = $file['type'];
                                                $extension = explode('.', $file['name'])[1];
                                                $fileTicket = $ticket . '.' . $extension;
                                                if ($mimetype == 'image/jpeg' || $mimetype == 'image/jpg' || $mimetype == 'image/png' || $mimetype == 'application/pdf') {
                                                        if (!is_dir('views/servicios/uploads/' . $nomUnidad)) {
                                                                mkdir('views/servicios/uploads/' . $nomUnidad, 0777, true);
                                                        }
                                                        if (file_exists('views/servicios/uploads/' . $nomUnidad . '/' . $fileTicket)) {
                                                                unlink('views/servicios/uploads/' . $nomUnidad . '/' . $fileTicket);
                                                        }
                                                        move_uploaded_file($file['tmp_name'], 'views/servicios/uploads/' . $nomUnidad . '/' . $fileTicket);
                                                        $ensacado->setDocTicket($fileTicket);
                                                }
                                        } else {
                                                $ensacado->setDocTicket($archivoTicket);
                                        }

                                        $r = $ensacado->edit();
                                        if ($r) {
                                                $result = [
                                                        'error' => true,
                                                        'mensaje' => 'Se edito correctamente.'
                                                ];
                                        } else {
                                                $result = [
                                                        'error' => false,
                                                        'mensaje' => 'Ocurrio un error, no se pudo editar.'
                                                ];
                                        }
                                }
                        }
                } else {
                        $result = [
                                'error' => false,
                                'mensaje' => 'Ocurrio un error, valide los datos.'
                        ];
                }

                print_r(json_encode($result));
        }

        public function eliminarDocumento()
        {
                if (isset($_POST['ferrotolva']) && $_POST['ferrotolva'] != '') {
                        $numeroFerro = $_POST['ferrotolva'];
                        $archivo = $_POST['documento'];
                        $id = $_POST['id'];
                        $tipo = $_POST['tipo'];
                        $result = false;
                        if (file_exists('views/servicios/uploads/' . $numeroFerro . '/' . $archivo)) {
                                unlink('views/servicios/uploads/' . $numeroFerro . '/' . $archivo);
                        }
                        if (is_dir('views/servicios/uploads/' . $numeroFerro)) {
                                $c = scandir('views/servicios/uploads/' . $numeroFerro);
                                if (count($c) < 2) {
                                        rmdir('views/servicios/uploads/' . $numeroFerro);
                                }
                        }
                        if ($tipo == 3) {
                                $e = new ServicioEnsacado();
                                $e->setid($id);
                                $r = $e->eliminarDocumento();
                        } else {
                                $e = new ServicioEntrada();
                                $e->setid($id);
                                $r = $e->eliminarDocumento($tipo);
                        }

                        if ($r) {
                                $result = [
                                        'error' => true,
                                        'mensaje' => 'Se elimino archivo.'
                                ];
                        } else {
                                $result = [
                                        'error' => false,
                                        'mensaje' => 'Ocurrio un error, no se pudo eliminar el archivo.'
                                ];
                        }
                }

                print_r(json_encode($result));
        }

        public function generarServicio()
        {
                $result = [];
                $id = isset($_POST['id']) && $_POST['id'] != '' ? $_POST['id'] : false;

                $entradaId = isset($_POST['idEntrada']) && $_POST['idEntrada'] != '' ? $_POST['idEntrada'] : false;
                $servicioId = isset($_POST['idTipoServicio']) && $_POST['idTipoServicio'] != '' ? $_POST['idTipoServicio'] : false;
                $empaqueId = isset($_POST['idEmpaque']) && $_POST['idEmpaque'] != '' ? $_POST['idEmpaque'] : null;
                $cantidad = isset($_POST['cantidad']) && $_POST['cantidad'] != '' ? $_POST['cantidad'] : null;
                $observaciones = isset($_POST['observaciones']) && $_POST['observaciones'] != '' ? $_POST['observaciones'] : null;
                $fechaPrograma = isset($_POST['fechaPrograma']) && $_POST['fechaPrograma'] != '' ? UtilsHelp::covertDatetoDateSql($_POST['fechaPrograma']) : null;
                $totalEnsacado = isset($_POST['totalEnsacado']) && $_POST['cantidad'] != '' ? $_POST['totalEnsacado'] : null;
                $barreduraLimpia = isset($_POST['barreduraLimpia']) && $_POST['barreduraLimpia'] != '' ? $_POST['barreduraLimpia'] : null;
                $barreduraSucia = isset($_POST['barreduraSucia']) && $_POST['barreduraSucia'] != '' ? $_POST['barreduraSucia'] : null;
                $bultos = isset($_POST['bultos']) && $_POST['bultos'] != '' ? $_POST['bultos'] : null;
                $tarimas = isset($_POST['tarimas']) && $_POST['tarimas'] != '' ? $_POST['tarimas'] : null;
                $tipoTarima = isset($_POST['tipoTarima']) && $_POST['tipoTarima'] != '' ? $_POST['tipoTarima'] : null;
                $parcial = isset($_POST['parcial']) && $_POST['parcial'] != '' ? $_POST['parcial'] : null;
                $estatus = isset($_POST['estatus']) && $_POST['estatus'] != '' ? $_POST['estatus'] : null;
                $lote = isset($_POST['lote']) && $_POST['lote'] != '' ? $_POST['lote'] : false;
                $archivoOrden = isset($_POST['archivoOrden']) && $_POST['archivoOrden'] != '' ? $_POST['archivoOrden'] : null;
                $orden = isset($_POST['orden']) && $_POST['orden'] != '' ? $_POST['orden'] : null;
                $productoId = isset($_POST['producto']) && $_POST['producto'] != '' ? $_POST['producto'] : null;
                $alias = isset($_POST['alias']) && $_POST['alias'] != '' ? $_POST['alias'] : null;
                $res = true;

                if ($entradaId && $servicioId) {
                        $ensacado = new ServicioEntrada();
                        $ensacado->setId($entradaId);
                        $e = $ensacado->getById();
                        $nomUnidad = $e['numUnidad'];

                        $servicio = new ServicioEnsacado();
                        $servicio->setEntradaId($entradaId);
                        $servicio->setServicioId($servicioId);
                        $servicio->setEmpaqueId($empaqueId);
                        $servicio->setEstatusId($estatus);
                        $servicio->setCantidad($cantidad != null ? Utils::stringToFloat($cantidad) : 'null');
                        $servicio->setObservaciones($observaciones);
                        $servicio->setFechaProgramacion($fechaPrograma == null ? null : date('Y-m-d', strtotime($fechaPrograma)));
                        $servicio->setTotalEnsacado($totalEnsacado != null ? Utils::stringToFloat($totalEnsacado) : 'null');
                        $servicio->setBarreduraSucia($barreduraSucia != null ? Utils::stringToFloat($barreduraSucia) : 'null');
                        $servicio->setBarreduraLimpia($barreduraLimpia != null ? Utils::stringToFloat($barreduraLimpia) : 'null');
                        $servicio->setBultos($bultos != null ? Utils::stringToFloat($bultos) : 'null');
                        $servicio->setTarimas($tarimas != null ? Utils::stringToFloat($tarimas) : 'null');
                        $servicio->setTipoTarima($tipoTarima != null ? Utils::stringToFloat($tipoTarima) : 'null');
                        $servicio->setParcial($parcial != null ? Utils::stringToFloat($parcial) : 'null');
                        $servicio->setProductoId($productoId);
                        $servicio->setAlias($alias);
                        $servicio->setLote($lote);
                        $servicio->setOrden($orden);

                        if (isset($_FILES['documentoOrden']) && $_FILES['documentoOrden']['size'] > 0) {
                                $file = $_FILES['documentoOrden'];
                                $mimetype = $file['type'];
                                $extension = explode('.', $file['name'])[1];
                                $fileOrden = $orden . '.' . $extension;

                                if ($mimetype == 'image/jpeg' || $mimetype == 'image/jpg' || $mimetype == 'image/png' || $mimetype == 'application/pdf') {
                                        if (!is_dir('views/servicios/uploads/' . $nomUnidad)) {
                                                mkdir('views/servicios/uploads/' . $nomUnidad . '/', 0777, true);
                                        }
                                        if (file_exists('views/servicios/uploads/' . $nomUnidad . '/' . $fileOrden)) {
                                                unlink('views/servicios/uploads/' . $nomUnidad . '/' . $fileOrden);
                                        }
                                        move_uploaded_file($file['tmp_name'], 'views/servicios/uploads/' . $nomUnidad . '/' . $fileOrden);
                                        $servicio->setDocOrden($fileOrden);
                                }
                        } else {
                                $servicio->setDocOrden($archivoOrden);
                        }

                        if (!$id) {
                                $servicio->setEstatusId(1);
                                $folioUltimoServ = $servicio->getUltimoServicio();
                                if ($folioUltimoServ != null) {
                                        $nextFolio = intval(UtilsHelp::recortarString($folioUltimoServ->folio, '-', true)) + 1;
                                        $servicio->setFolio('SERV-' . $nextFolio);
                                } else {
                                        $servicio->setFolio('SERV-1');
                                }

                                $r = $servicio->save();

                                if ($r) {
                                        $result = [
                                                'error' => true,
                                                'mensaje' => 'Se guardo correctamente.'
                                        ];
                                } else {
                                        $result = [
                                                'error' => false,
                                                'mensaje' => 'Ocurrio un error, no se pudo guardar.'
                                        ];
                                }
                        } else {
                                $servicio->setId($id);
                                if ($fechaPrograma != null && $estatus == 1) {
                                        $servicio->setEstatusId(13);
                                        // programada
                                }

                                $r = $servicio->edit();
                                if ($r) {
                                        $result = [
                                                'error' => true,
                                                'mensaje' => 'Se edito correctamente.'
                                        ];
                                } else {
                                        $result = [
                                                'error' => false,
                                                'mensaje' => 'Ocurrio un error, no se pudo editar.'
                                        ];
                                }
                        }
                } else {
                        $result = [
                                'error' => false,
                                'mensaje' => 'Ocurrio un error, valide los datos.'
                        ];
                }

                print_r(json_encode($result));
        }

        public function getServicio()
        {
                $id = isset($_POST['id']) && $_POST['id'] != '' ? $_POST['id'] : null;
                $servicio = new ServicioEnsacado();
                $servicio->setId($id);
                $s = $servicio->getById();

                return print_r(json_encode($s));
        }

        public function iniciarServicio()
        {
                $id = isset($_POST['id']) && $_POST['id'] != '' ? $_POST['id'] : null;
                $servicio = new ServicioEnsacado();
                $servicio->setId($id);
                $r = $servicio->inicarServicio();
                if ($r) {
                        $result = [
                                'error' => true,
                                'mensaje' => 'Se inicio servicio.'
                        ];
                } else {
                        $result = [
                                'error' => false,
                                'mensaje' => 'Ocurrio un error, no se pudo iniciar.'
                        ];
                }

                return print_r(json_encode($result));
        }

        public function finalizarServicio()
        {
                $idServicio = isset($_POST['idServicioEnviar']) && $_POST['idServicioEnviar'] != '' ? $_POST['idServicioEnviar'] : null;
                $cantidades = isset($_POST['cantidadAlmacen']) ? $_POST['cantidadAlmacen'] : null;
                $almacenes = isset($_POST['almacen']) ? $_POST['almacen'] : null;
                $operacion = isset($_POST['operacionEnviar']) ? $_POST['operacionEnviar'] : null;
                $BarreduraSucia = isset($_POST['BarreduraSucia']) ? $_POST['BarreduraSucia'] : null;
                $BarreduraLimpia = isset($_POST['BarreduraLimpia']) ? $_POST['BarreduraLimpia'] : null;

                if ($operacion != 'E') {
                        $m = new ServicioMovimientoAlmacen();
                        $m->setAlmacen(intval($almacenes));
                        $m->setCantidad(Utils::quitarComas($cantidades));
                        $m->setIdServicio($idServicio);
                        $m->setOperacion($operacion);
                        $r = $m->save();
                        $servicio = new ServicioEnsacado();
                        $servicio->setId($idServicio);
                        $r = $servicio->finalizarServicio();
                        if ($r) {
                                $result = [
                                        'error' => true,
                                        'mensaje' => 'Se finalizo servicio.'
                                ];
                        } else {
                                $result = [
                                        'error' => false,
                                        'mensaje' => 'Ocurrio un error, no se pudo finalizar.'
                                ];
                        }
                } else {
                        $m = new ServicioMovimientoAlmacen();
                        $ser = new ServicioEnsacado();
                        for ($i = 0; count($almacenes) > $i; $i++) {
                                $m->setAlmacen(intval($almacenes[$i]));
                                $m->setCantidad(Utils::quitarComas($cantidades[$i]));
                                $m->setIdServicio($idServicio);
                                $m->setOperacion($operacion);
                                $r = $m->save();

                                $ser->setId($idServicio);
                                $ser->setBarreduraSucia(Utils::quitarComas($BarreduraSucia[$i]));
                                $ser->setBarreduraLimpia(Utils::quitarComas($BarreduraLimpia[$i]));
                                $ser->setTotalEnsacado(Utils::quitarComas($cantidades[$i]));
                                $ser->setTarimas(floor((Utils::quitarComas($cantidades[$i]) / 25) / 55));
                                $ser->setBultos(floor(Utils::quitarComas($cantidades[$i]) / 25));
                                $ser->setParcial(round((((Utils::quitarComas($cantidades[$i]) / 25) / 55) - floor((Utils::quitarComas($cantidades[$i]) / 25) / 55)) * 55));
                                $ser->actualizaBarredura();
                        }
                        if ($r) {
                                $servicio = new ServicioEnsacado();
                                $servicio->setId($idServicio);
                                $r = $servicio->finalizarServicio();
                                if ($r) {
                                        $result = [
                                                'error' => true,
                                                'mensaje' => 'Se finalizo servicio.'
                                        ];
                                } else {
                                        $result = [
                                                'error' => false,
                                                'mensaje' => 'Ocurrio un error, no se pudo finalizar.'
                                        ];
                                }
                        } else {
                                $result = [
                                        'error' => false,
                                        'mensaje' => 'Ocurrio un error, no se pudo guardar en el almacén.'
                                ];
                        }
                }
                return print_r(json_encode($result));
        }

        public function eliminarServicioNave()
        {
                $id = isset($_POST['id']) && $_POST['id'] != '' ? $_POST['id'] : null;
                $servicio = new ServicioEnsacado();
                $servicio->setId($id);
                $r = $servicio->delete();
                if ($r) {
                        $result = [
                                'error' => true,
                                'mensaje' => 'Se elimino servicio.'
                        ];
                } else {
                        $result = [
                                'error' => false,
                                'mensaje' => 'Ocurrio un error, no se pudo eliminar servicio.'
                        ];
                }

                return print_r(json_encode($result));
        }

        public function crearEntrada()
        {
                Utils::noLoggin();

                $cliente = new Cliente();
                $clientes = $cliente->getClienteConServiciosEnsacado();

                $tipoTrans = new TipoTransporte();
                $transportes = $tipoTrans->getAll();

                require_once views_root . 'servicios/cargas_descargas.php';
        }

        public function liberaUnidad()
        {
                $id = isset($_POST['id']) && $_POST['id'] != '' ? $_POST['id'] : null;
                if ($id != null) {
                        $servicio = new ServicioEntrada();
                        $servicio->setId($id);
                        $r = $servicio->liberaUnidad();
                        if ($r) {
                                $result = [
                                        'error' => true,
                                        'mensaje' => 'Se registro la salida de la unidad.'
                                ];
                        } else {
                                $result = [
                                        'error' => false,
                                        'mensaje' => 'Ocurrio un error.'
                                ];
                        }
                } else {
                        $result = [
                                'error' => false,
                                'mensaje' => 'Ocurrio un error.'
                        ];
                }
                return print_r(json_encode($result));
        }

        public function ingresarUnidad()
        {
                $id = isset($_POST['id']) && $_POST['id'] != '' ? $_POST['id'] : null;
                if ($id != null) {
                        $servicio = new ServicioEntrada();
                        $servicio->setId($id);
                        $r = $servicio->ingresarUnidad();
                        if ($r) {
                                $result = [
                                        'error' => true,
                                        'mensaje' => 'Se registro la entrada de la unidad.'
                                ];
                        } else {
                                $result = [
                                        'error' => false,
                                        'mensaje' => 'Ocurrio un error.'
                                ];
                        }
                } else {
                        $result = [
                                'error' => false,
                                'mensaje' => 'No se encontro unidad.'
                        ];
                }
                return print_r(json_encode($result));
        }

        public function transitoUnidad()
        {
                $id = isset($_POST['id']) && $_POST['id'] != '' ? $_POST['id'] : null;
                if ($id != null) {
                        $servicio = new ServicioEntrada();
                        $servicio->setId($id);
                        $r = $servicio->transitoUnidad();
                        if ($r) {
                                $result = [
                                        'error' => true,
                                        'mensaje' => 'La unidad esta en transito.'
                                ];
                        } else {
                                $result = [
                                        'error' => false,
                                        'mensaje' => 'Ocurrio un error.'
                                ];
                        }
                } else {
                        $result = [
                                'error' => false,
                                'mensaje' => 'No se encontro unidad.'
                        ];
                }
                return print_r(json_encode($result));
        }

        public function salidaUnidad()
        {
                $id = isset($_POST['id']) && $_POST['id'] != '' ? $_POST['id'] : null;
                if ($id != null) {
                        $servicio = new ServicioEntrada();
                        $servicio->setId($id);
                        $r = $servicio->salidaUnidad();
                        if ($r) {
                                $result = [
                                        'error' => true,
                                        'mensaje' => 'La unidad esta en salida.'
                                ];
                        } else {
                                $result = [
                                        'error' => false,
                                        'mensaje' => 'Ocurrio un error.'
                                ];
                        }
                } else {
                        $result = [
                                'error' => false,
                                'mensaje' => 'No se encontro unidad.'
                        ];
                }
                return print_r(json_encode($result));
        }

        public function serviciosNave()
        {
                Utils::noLoggin();
                $idEst = null;
                $ensacado = new ServicioEnsacado();
                $servicios = $ensacado->getAll();

                require_once views_root . 'servicios/lista_servicios_nave.php';
        }

        public function imprimirServicio($id = null, $mostrar = true)
        {
                if (isset($_GET['idServ']) && $_GET['idServ'] != '') {
                        $id = $_GET['idServ'];
                }
                if ($id != null) {
                        require_once utils_root . 'toPDF/pdf.php';
                        $serv = new ServicioEnsacado();
                        $serv->setId($id);
                        $s = $serv->getById();
                        PDF::crearPdfServicio($s, $mostrar);
                }
        }

        public function serviciosAlmacen()
        {
                $idEst = isset($_GET['idEst']) && $_GET['idEst'] != '' ? intval($_GET['idEst']) : null;
                Utils::noLoggin();
                $ensacado = new ServicioEntrada();
                switch ($idEst) {
                        case 1:
                                $lotes = $ensacado->getCLientes();

                                var_dump($lotes);
                                break;

                        case 2:
                                break;
                        default:
                                $lotes = $ensacado->getLotes();
                                $lotesAlmacen = array();

                                for ($i = 0;
                                        count($lotes) > $i;
                                        $i++) {
                                        $servicio = new ServicioEnsacado();
                                        $s = $servicio->getAlmacenLotes($lotes[$i]['lote']);

                                        if (!empty($s)) {
                                                $total = 0;
                                                $cargas = 0;
                                                $descargas = 0;

                                                $fecha = null;
                                                $isDescarga = false;

                                                foreach ($s as $serv) {
                                                        if ($serv->clave == 'CARGA' || $serv->clave == 'AJUSTE') {
                                                                $total = $serv->cantidad;
                                                                $cargas += $serv->cantidad;

                                                                $isDescarga = true;
                                                        } else {
                                                                $total = $serv->total_ensacado;
                                                                $descargas += $serv->total_ensacado;
                                                                $isDescarga = false;
                                                        }

                                                        $serv->total = $total;
                                                        $serv->fecha = $fecha;
                                                        $serv->isDescarga = $isDescarga;
                                                }
                                                $lotes[$i]['descargas'] = $descargas;
                                                $lotes[$i]['cargas'] = $cargas;
                                                $lotes[$i]['stock'] = $descargas - $cargas;
                                                $lotes[$i]['servicios'] = $s;

                                                $lotes[$i]['servicios'] = $s;
                                                array_push($lotesAlmacen, $lotes[$i]);
                                        }
                                }
                                break;
                }

                require_once views_root . 'servicios/lista_almacen.php';
        }

        public function getInfoLote()
        {
                if (isset($_POST['lote']) && $_POST['lote'] != '') {
                        $lote = $_POST['lote'];

                        $ensacado = new ServicioEnsacado();
                        $l = $ensacado->getInfoLote($lote);
                        return print_r(json_encode($l));
                }
        }

        public function getLotesByClienteStock()
        {
                if (isset($_POST['idCliente']) && $_POST['idCliente'] != '') {
                        $s = new ServicioEnsacado();
                        $idCli = $_POST['idCliente'];
                        $lotes = $s->getLotesCliente($idCli);

                        return print_r(json_encode($lotes));
                }
        }

        public function getClientesYTransportes()
        {
                $cliente = new Cliente();
                $clientes = $cliente->getClienteConServiciosEnsacado();

                $tipoTrans = new TipoTransporte();
                $transportes = $tipoTrans->getAll();

                echo json_encode(['mensaje' => 'OK', 'clientes' => $clientes, 'transportes' => $transportes]);
                return true;
        }

        public function listaUnidades()
        {
                Utils::noLoggin();
                $idEst = null;
                $PesoPend = 0;

                if (isset($_GET['idEst'])) {
                        $idEst = (int) $_GET['idEst'];
                }
                if (isset($_GET['PesoPend'])) {
                        $PesoPend = (int) $_GET['PesoPend'];
                }

                require_once views_root . 'servicios/lista_unidades.php';
        }

        public function getUnidadesAPP()
        {
                $idEst = null;
                $PesoPend = 0;
                if (isset($_POST['idEst'])) {
                        if ($_POST['idEst'] == '8') {
                                $idEst = '1,8';
                        } else {
                                $idEst = (int) $_POST['idEst'];
                        }
                }
                if (isset($_GET['PesoPend'])) {
                        $PesoPend = (int) $_POST['PesoPend'];
                }
                $trenArray = Utils::isTren();
                $arrayIdsTr = array();
                foreach ($trenArray as $tr) {
                        array_push($arrayIdsTr, $tr->id);
                }
                $ensacado = new ServicioEntrada();
                $servicios = $ensacado->getByEstatusId($idEst, $PesoPend);
                echo json_encode(['mensaje' => 'OK', 'servicios' => $servicios]);
        }

        public function getCantidadesServicios()
        {
                $ensacado = new ServicioEntrada();
                $servicios = $ensacado->getCantidadesServicios();
                $etapas = [];
                foreach ($servicios as $tr) {
                        $etapas[] = [
                                'Etapa' => $tr['Etapa'],
                                'Cantidad' => $tr['Cantidad']
                        ];
                }
                echo json_encode(['mensaje' => 'OK', 'etapas' => $etapas]);
        }

        public function appEnsacado()
        {
                Utils::noLoggin();
                require_once views_root . 'servicios/app_ensacado.php';
        }

        public function appCargas()
        {
                Utils::noLoggin();
                require_once views_root . 'servicios/app_cargas.php';
        }

        public function getServDescargas()
        {
                $ensacado = new ServicioEnsacado();
                $servicios = $ensacado->getServDescargas();
                echo json_encode(['mensaje' => 'OK', 'servDescargas' => $servicios]);
        }

        public function getServCargas()
        {
                $ensacado = new ServicioEnsacado();
                $servicios = $ensacado->getServCargas();
                echo json_encode(['mensaje' => 'OK', 'servCargas' => $servicios]);
        }

        public function ordenSalida()
        {
                Utils::noLoggin();
                $entrada = new ServicioEntrada();
                $entrada->setId($_GET['id']);
                $registro = $entrada->getById();
                $ensacado = new ServicioEnsacado();
                $ensacado->getAll('where s.entrada_id = ' . $_GET['id']);
                $cliente = isset($_POST['cliente']) ? $_POST['cliente'] : 'nombre del cliente';
                require_once views_root . 'servicios/assets/formatos/orden_salida.php';
        }
}