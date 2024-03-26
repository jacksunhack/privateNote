<?php

function generateRSAKeys(){
    $config = array(
        "digest_alg" => "sha512",
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    );
    $res = openssl_pkey_new($config);
    openssl_pkey_export($res, $privKey);
    $pubKey = openssl_pkey_get_details($res);
    $pubKey = $pubKey["key"];
    return array('privateKey' => $privKey, 'publicKey' => $pubKey);
}

function saveMessage($connection, $id, $message, $publicKey, $privateKey){
    openssl_public_encrypt($message, $encrypted, $publicKey);
    $message = base64_encode($encrypted);
    $sql = "INSERT INTO `note` (`id`, `message`, `public_key`, `private_key`)
            VALUES (:id, :message, :public_key, :private_key);";
    $sth = $connection->prepare($sql);
    $sth->execute(array(':id' => $id, ':message' => $message, ':public_key' => $publicKey, ':private_key' => $privateKey));
}

function getMessage($connection, $id){
    $sql = "SELECT `message`, `public_key`, `private_key` FROM `note`
            WHERE `id` = :id";
    $sth = $connection->prepare($sql);
    $sth->execute(array(':id' => $id));
    $enregistrements = $sth->fetchObject();
    if(!$enregistrements) return 'This note was read and destroyed';
    $encrypted = base64_decode($enregistrements->message);
    $privateKey = $enregistrements->private_key;
    $privateKeyResource = openssl_pkey_get_private($privateKey);
    openssl_private_decrypt($encrypted, $decrypted, $privateKeyResource);
    deleteMessage($connection, $id);
    return $decrypted;
}

function deleteMessage($connection, $id){
    $sql = "DELETE FROM `note`
    WHERE `id` = :id";
    $sth = $connection->prepare($sql);
    $sth->execute(array(':id' => $id));
}

?>
