<?php

require 'DocumentController.php';

/**
 * Gestion des livres
 */
class BookController extends DocumentController
{
    /**
     * Mise à jour d'un livre et de son document, fields doit contenir les valeurs :
     * ISBN :String
     * Auteur :String
     * Collection :String
     * Titre :String,
     * Image :String,
     * IdRayon :String (ref rayon),
     * IdGenre :String (ref genre),
     * IdPublic :String (ref public)
     */
    public function update($tableName, $id, $fields)
    {
        return Db::update('livre', $id, [
            'ISBN' => $fields->ISBN,
            'auteur' => $fields->Auteur,
            'collection' => $fields->Collection
        ]) && Db::update('document', $id, [
            'titre' => $fields->Titre,
            'image' => $fields->Image,
            'idRayon' => $fields->IdRayon,
            'idGenre' => $fields->IdGenre,
            'idPublic' => $fields->IdPublic
        ]);
    }

    /**
     * Création d'un nouveau document livre
     */
    public function create($tableName, $fields)
    {
        $lastDocument = Db::queryFirst('SELECT id FROM livre ORDER BY id DESC LIMIT 1');
        if ($lastDocument != null) {
            $id = '' . (intval($lastDocument['id']) + 1);
            while (strlen($id) < 5) {
                $id = '0' . $id;
            }
            if ($id[0] != '0') {
                $id = '0' . substr($id, 1);
            }
        } else {
            $id = '00001';
        }

        if (Db::insert('document', [
            'id' => $id,
            'titre' => $fields->Titre,
            'image' => $fields->Image,
            'idRayon' => $fields->IdRayon,
            'idGenre' => $fields->IdGenre,
            'idPublic' => $fields->IdPublic
        ]) && Db::insert('livres_dvd', [
            'id' => $id
        ]) && Db::insert('livre', [
            'id' => $id,
            'ISBN' => $fields->ISBN,
            'auteur' => $fields->Auteur,
            'collection' => $fields->Collection
        ])) {
            $fields->id = $id;
            return $fields;
        } else {
            return null;
        }
    }
}
