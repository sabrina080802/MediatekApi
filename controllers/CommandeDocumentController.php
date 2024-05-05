<?php

/**
 * Gestion des liaison de livres et dvd liés aux commandes
 */
class CommandeDocumentController extends DataController
{
    /**
     * Mise à jour du nombre d'exemplaires
     */
    public function update($tableName, $id, $fields)
    {
        foreach ($fields as $key => $value) {
            if (Db::execute('UPDATE commandedocument SET nbExemplaire = :nbExemplaire WHERE id = :id AND idLivreDvd = :idLivreDvd', [
                'id' => $id,
                'idLivreDvd' => $key,
                'nbExemplaire' => $value,
            ]) !== true) {
                return false;
            }
        }

        return true;
    }
    /**
     * Création d'une liaison d'un dvd ou livre à une commande
     */
    public function create($tableName, $fields)
    {
        $existing = Db::queryFirst('SELECT * FROM commandedocument WHERE id = :id AND idLivreDvd = :idLivreDvd', [
            'id' => $fields->id,
            'idLivreDvd' => $fields->idLivreDvd
        ]);
        if ($existing == null) {
            return Db::insert('commandedocument', [
                'id' => $fields->id,
                'idLivreDvd' => $fields->idLivreDvd,
                'nbExemplaire' => $fields->nbExemplaire
            ]);
        } else {
            return Db::execute('UPDATE commandedocument SET nbExemplaire = nbExemplaire + :nbExemplaire WHERE id = :id AND idLivreDvd = :idLivreDvd', [
                'id' => $fields->id,
                'idLivreDvd' => $fields->idLivreDvd,
                'nbExemplaire' => $fields->nbExemplaire
            ]);
        }
    }

    /**
     * Pas d'implémentation car on ne supprime pas la liaison car elle se supprime automatiquement lors de la suppression d'une commande
     */
    public function delete($tableName, $id)
    {
    }

    /**
     * Cherche toutes les liaison de livre / dvd -> commande selon les paramètres fields
     * Pour chaque liaison, renvoie également le titre du livre ou du dvd
     */
    public function find($tableName, $fields)
    {
        $fieldList = [];
        $where = '';
        $isFirst = true;
        foreach ($fields as $key => $value) {
            if ($isFirst == false) {
                $where .= ' AND';
            } else {
                $isFirst = false;
            }

            if ($key == 'id') {
                $where .= " $tableName.id LIKE :$key";
                $fieldList[$key] = "%$value%";
            } else if (strpos($key, 'id') !== false) {
                $where .= " $key = :$key";
                $fieldList[$key] = $value;
            } else {
                $where .= " $key LIKE :$key";
                $fieldList[$key] = "%$value%";
            }
        }

        $request = "SELECT $tableName.*, document.titre FROM $tableName INNER JOIN document ON document.id = $tableName.idLivreDvd WHERE$where";
        return Db::query($request, $fieldList);
    }
}
