<?php

require 'DocumentController.php';

class ReviewController extends DocumentController
{
    /**
     * Mise à jour d'un livre et de son document, fields doit contenir les valeurs :
     * periodicite :String
     * delaiMiseADispo :String
     * Titre :String,
     * Image :String,
     * IdRayon :String (ref rayon),
     * IdGenre :String (ref genre),
     * IdPublic :String (ref public)
     */
    public function update($tableName, $id, $fields)
    {
        return Db::update('revue', $id, [
            'periodicite' => $fields->Periodicite,
            'delaiMiseADispo' => $fields->DelaiMiseADispo
        ]) && Db::update('document', $id, [
            'titre' => $fields->Titre,
            'image' => $fields->Image,
            'idRayon' => $fields->IdRayon,
            'idGenre' => $fields->IdGenre,
            'idPublic' => $fields->IdPublic
        ]);
    }
    /** Création d'une revue */
    public function create($tableName, $fields)
    {
        $lastDocument = Db::queryFirst('SELECT id FROM revue ORDER BY id DESC LIMIT 1');
        if ($lastDocument != null) {
            $id = '' . (intval($lastDocument['id']) + 1);
            while (strlen($id) < 5) {
                $id = '1' . $id;
            }
            if ($id[0] != '1') {
                $id = '1' . substr($id, 1);
            }
        } else {
            $id = '10001';
        }

        if (Db::insert('document', [
            'id' => $id,
            'titre' => 'Sans titre',
            'image' => $fields->Image,
            'idRayon' => $fields->IdRayon,
            'idGenre' => $fields->IdGenre,
            'idPublic' => $fields->IdPublic
        ]) && Db::insert('revue', [
            'id' => $id,
            'periodicite' => $fields->Periodicite,
            'delaiMiseADispo' => $fields->DelaiMiseADispo
        ])) {
            $fields->id = $id;
            return $fields;
        }
    }
    public function delete($tableName, $id)
    {
        return Db::delete($tableName, $id) && Db::delete('document', $id);
    }
}
