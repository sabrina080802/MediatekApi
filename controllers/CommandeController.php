<?php

/**
 * Gestion des commandes
 */
class CommandeController extends DataController
{
    /** 
     * Mise à jour des informations d'un document. 
     * Selon ce qui est fourni en field, la fonction met également à jour les informations d'un abonnement de revue ou d'un commandedocument
     */
    public function update($tableName, $id, $fields)
    {
        $commandInfos = Db::queryFirst('SELECT * FROM commande WHERE id = :id', ['id' => $id]);
        if ($commandInfos == null || intval($commandInfos['idsuivi']) > intval($fields->IdSuivi)) {
            return false;
        }

        $deliveredState = Db::queryFirst('SELECT * FROM suivi WHERE libelle = "Livrée"');
        if ($deliveredState['id'] == $fields->IdSuivi) {
            $commandContent = Db::query('SELECT * FROM commandedocument WHERE id = :id', ['id' => $id]);
            $contentCount = sizeof($commandContent);
            $newCondition = Db::queryFirst('SELECT * FROM etat WHERE libelle = "neuf"');

            for ($i = 0; $i < $contentCount; $i++) {
                $documentData = Db::queryFirst('SELECT * FROM livre INNER JOIN document ON document.id = livre.id WHERE livre.id = :id', ['id' => $commandContent[$i]['idLivreDvd']]);
                if ($documentData == null) {
                    $documentData = Db::queryFirst('SELECT * FROM revue INNER JOIN document ON document.id = livre.id WHERE livre.id = :id', ['id' => $commandContent[$i]['idLivreDvd']]);

                    if ($documentData == null) {
                        continue;
                    }
                }

                $count = intval($commandContent[$i]['nbExemplaire']);
                $lastExemplaire = Db::queryFirst('SELECT * FROM exemplaire WHERE id = :id ORDER BY numero DESC LIMIT 1', ['id' => $commandContent[$i]['idLivreDvd']]);
                $numero = 1;
                if ($lastExemplaire != null) {
                    $numero = intval($lastExemplaire['numero']) + 1;
                }

                for ($j = 0; $j < $count; $j++) {
                    Db::insert('exemplaire', [
                        'id' => $commandContent[$i]['idLivreDvd'],
                        'numero' => $numero++,
                        'dateAchat' => $commandInfos['dateCommande'],
                        'photo' => $documentData['image'],
                        'idEtat' => $newCondition['id']
                    ]);
                }
            }
        }

        return Db::update('commande', $id, [
            'idsuivi' => $fields->IdSuivi
        ]);
    }

    /**
     * Créer une nouvelle commande en générant un nouvel id
     */
    public function create($tableName, $fields)
    {
        $lastCommand = Db::queryFirst('SELECT id FROM commande ORDER BY id DESC LIMIT 1');
        if ($lastCommand != null) {
            $id = '' . (intval($lastCommand['id']) + 1);
            while (strlen($id) < 5) {
                $id = '0' . $id;
            }
        } else {
            $id = '00001';
        }

        $data = [
            'id' => $id,
            'dateCommande' => new DateTime(),
            'montant' => 0,
            'idsuivi' => 0
        ];

        if (property_exists($fields, 'IdLivreDvd')) {
            if (Db::insert('commande', $data) && Db::insert('commandedocument', [
                'id' => $id,
                'idLivreDvd' => $fields->IdLivreDvd,
                'nbExemplaire' => 1
            ])) {
                return $data;
            } else {
                return null;
            }
        } else if (property_exists($fields, 'IdRevue') && property_exists($fields, 'DateFinAbonnement')) {
            if (Db::insert('commande', $data) && Db::insert('abonnement', [
                'id' => $id,
                'dateFinAbonnement' => new DateTime($fields->DateFinAbonnement),
                'idRevue' => $fields->IdRevue
            ])) {
                return $data;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
    /**
     * Supprime une commande par son id en vérifiant tout d'abord si il n'y a pas d'abonnement associé à cette commande
     * Elle vérifie également l'état de la commande, une commande ne peut être supprimée que si elle est réglée
     */
    public function delete($tableName, $id)
    {
        $subscription = Db::queryFirst('SELECT * FROM abonnement WHERE id = :id', ['id' => $id]);
        if ($subscription != null) {
            return Db::delete('abonnement', $id) && Db::delete('commande', $id);
        }
        $commandeData = Db::queryFirst('SELECT * FROM commande WHERE id = :id', ['id' => $id]);
        if ($commandeData == null || $commandeData['idsuivi'] != 2) { //idSuivi = 2 : commande réglée
            return false;
        }

        return Db::delete('commandedocument', $id) &&
            Db::delete('commande', $id);
    }

    /**
     * Recherche une commande
     * Si le champs idLivreDvd est fourni, renvoie les commandes en cherchant par la présence d'un livre ou dvd fourni en paramètre parmis les commandedocument liés à cette commande
     * Si le champs idRevue est fourni, renvoie les commandes en cherchant par la présence de la revue fournie en paramètre
     */
    public function find($tableName, $fields)
    {
        if (property_exists($fields, 'idLivreDvd')) {
            return Db::query(
                'SELECT commande.* FROM commande
                INNER JOIN commandedocument ON commandedocument.id = commande.id
                WHERE commandedocument.idLivreDvd LIKE :idLivreDvd',
                ['idLivreDvd' => $fields->idLivreDvd]
            );
        } else if (property_exists($fields, 'idRevue')) {
            return Db::query(
                'SELECT commande.* FROM commande
                INNER JOIN abonnement ON abonnement.id = commande.id
                WHERE abonnement.idRevue = :idRevue
                ORDER BY abonnement.dateFinAbonnement DESC',
                ['idRevue' => $fields->idRevue]
            );
        }

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

        $request = "SELECT * FROM $tableName WHERE$where";
        return Db::query($request, $fieldList);
    }
}
