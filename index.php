<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require "classes/Image.class.php";
    //validando dados recebidos
    $validate = Image::validate($_POST, $_FILES, $_GET);
    //se a validação não estiver certa ele dá o response de rro
    if ($validate != 1) {
        http_response_code($validate['status']);
        echo json_encode($validate['data']);
        return false;
    }
    //definindo método padrão
    $method = isset($_GET['type']) && $_GET['type'] != null ? $_GET['type'] : "compress";
    //verificando se o metodo existe
    if (!Image::method_exist($method)) {
        $method = "compress";
    }
    //verificando se as dimensões do arquivo são validas no caso de imagem
    if(!Image::validateSize($method, $_GET)){
        $method = 'compress';
    }
    //verificando se o tipo de arquivo e img
    if (Image::getMime($_FILES) != "img") {
        $method = "upload";
    }
    //caso o arquivo seja img e o metodo upload executa o compress
    if (Image::getMime($_FILES) == "img" && $method == 'upload') {
        $method = 'compress';
    }
    //executa o método e mostra o retorno
    $response = Image::$method($_POST, $_FILES, $_GET);
    http_response_code($response['status']);
    echo json_encode($response['data']);
    return true;
}