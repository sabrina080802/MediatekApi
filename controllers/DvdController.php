<?php

require 'DocumentController.php';

/** Gestion des DVD */
class DvdController extends DocumentController
{
    /**
     * Mise à jour d'un DVD et de son document, fields doit contenir les valeurs :
     * synopsis :String
     * realisateur :String
     * duree :Int32
     * Titre :String,
     * Image :String,
     * IdRayon :String (ref rayon),
     * IdGenre :String (ref genre),
     * IdPublic :String (ref public)
     */
    public function update($tableName, $id, $fields)
    {
        return Db::update('dvd', $id, [
            'synopsis' => $fields->Synopsis,
            'realisateur' => $fields->Realisateur,
            'duree' => $fields->Duree
        ]) && Db::update('document', $id, [
            'titre' => $fields->Titre,
            'image' => $fields->Image,
            'idRayon' => $fields->IdRayon,
            'idGenre' => $fields->IdGenre,
            'idPublic' => $fields->IdPublic
        ]);
    }

    /**
     * Création d'un nouveau DVD
     */
    public function create($tableName, $fields)
    {
        $lastDocument = Db::queryFirst('SELECT id FROM dvd ORDER BY id DESC LIMIT 1');
        if ($lastDocument != null) {
            $id = '' . (intval($lastDocument['id']) + 1);
            while (strlen($id) < 5) {
                $id = '2' . $id;
            }
            if ($id[0] != '2') {
                $id = '2' . substr($id, 1);
            }
        } else {
            $id = '20001';
        }

        if (Db::insert('document', [
            'id' => $id,
            'titre' => 'Sans titre',
            'image' => $fields->Image,
            'idRayon' => $fields->IdRayon,
            'idGenre' => $fields->IdGenre,
            'idPublic' => $fields->IdPublic
        ]) && Db::insert('livres_dvd', [
            'id' => $id
        ]) && Db::insert('dvd', [
            'id' => $id,
            'synopsis' => $fields->Synopsis,
            'realisateur' => $fields->Realisateur,
            'duree' => $fields->Duree
        ])) {
            $fields->id = $id;
            return $fields;
        } else {
            return null;
        }
    }
}
