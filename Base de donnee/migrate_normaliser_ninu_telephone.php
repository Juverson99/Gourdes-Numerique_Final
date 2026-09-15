<?php
/**
 * migrate_normaliser_ninu_telephone.php
 *
 * Met à jour les comptes DÉJÀ EN BASE pour respecter le nouveau format
 * obligatoire du NINU et du téléphone (voir les fonctions validate_ninu(),
 * validate_phone(), normalize_ninu() et normalize_phone() dans
 * config/config.php, qui sont la seule source de vérité pour ce format et
 * sont réutilisées ici telles quelles — pas de logique dupliquée) :
 *
 *   - NINU      : uniquement des chiffres, exactement NINU_LENGTH (10) chiffres
 *   - Téléphone : indicatif PHONE_INDICATIF ("509") + un espace + PHONE_LOCAL_LENGTH
 *                 (8) chiffres, ex. "509 46213235"
 *
 * Le même format de téléphone s'applique désormais aussi aux dossiers gérés
 * depuis l'admin qui comportent un champ téléphone facultatif : institutions
 * financières, fournisseurs et agences BNC (voir FinancialInstitution::validate(),
 * Supplier::validate() et BncAgence::validate()). Ce script normalise donc,
 * en plus des comptes "users", les téléphones déjà enregistrés dans
 * financial_institutions, suppliers et bnc_agences.
 *
 * À exécuter UNE SEULE FOIS après une mise à jour du code introduisant ces
 * contraintes, sur une base qui contient déjà des comptes/fiches enregistrés
 * sous l'ancien format libre (ex. "NINU-1123-8890", "3712-3456").
 *
 * Ce script est prudent :
 *   - Une ligne dont le NINU ou le téléphone ne peut pas être ramené au bon
 *     format une fois débarrassé de tout caractère non numérique (ex. NINU
 *     de 7 ou 12 chiffres au lieu de 10) N'EST PAS MODIFIÉE automatiquement :
 *     elle est simplement signalée en fin d'exécution, à corriger à la main
 *     depuis /admin (le formulaire refusera alors de toute façon
 *     l'enregistrement tant que le format n'est pas respecté).
 *   - Pour la table "users" (seule à imposer un téléphone obligatoire et
 *     UNIQUE en base), si la normalisation de deux comptes différents
 *     aboutirait au même NINU ou au même téléphone (doublon), aucun des deux
 *     n'est modifié : ils sont également signalés, à traiter manuellement au
 *     cas par cas. Le téléphone est facultatif (et non UNIQUE) pour les
 *     institutions, fournisseurs et agences BNC : une ligne sans téléphone
 *     est simplement ignorée, sans vérification de doublon.
 *   - Rien n'est écrit tant que l'aperçu n'a pas été validé : lancer le
 *     script une première fois sans argument affiche uniquement un aperçu
 *     des changements prévus (aucune écriture) ; ajouter --appliquer pour
 *     effectuer réellement les mises à jour.
 *
 * Utilisation :
 *   php migrate_normaliser_ninu_telephone.php               (aperçu seul)
 *   php migrate_normaliser_ninu_telephone.php --appliquer    (applique les changements)
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Ce script s'exécute uniquement en ligne de commande (CLI), jamais via le navigateur.\n");
}

require_once __DIR__ . '/config/config.php';

$apply = in_array('--appliquer', $argv, true);

$pdo = db();
$users = $pdo->query('SELECT id, name, ninu, phone FROM users ORDER BY id')->fetchAll();

$toUpdate   = [];   // id => ['ninu' => ..., 'phone' => ...] (au moins un des deux change)
$unresolved = [];   // id => raison (format toujours invalide après nettoyage)

// Détecte les collisions : deux comptes différents qui normaliseraient vers
// la même valeur ne doivent jamais être écrits (contrainte UNIQUE en base).
$ninuTargets  = [];  // valeur normalisée => [ids]
$phoneTargets = [];

foreach ($users as $u) {
    $id = (int) $u['id'];

    $newNinu  = normalize_ninu((string) $u['ninu']);
    $newPhone = normalize_phone((string) $u['phone']);

    if (validate_ninu($newNinu) === null) {
        $ninuTargets[$newNinu][] = $id;
    }
    if (validate_phone($newPhone) === null) {
        $phoneTargets[$newPhone][] = $id;
    }
}

foreach ($users as $u) {
    $id       = (int) $u['id'];
    $oldNinu  = (string) $u['ninu'];
    $oldPhone = (string) $u['phone'];

    $newNinu  = normalize_ninu($oldNinu);
    $newPhone = normalize_phone($oldPhone);

    $ninuError  = validate_ninu($newNinu);
    $phoneError = validate_phone($newPhone);

    $reasons = [];
    if ($ninuError) {
        $reasons[] = "NINU : \"$oldNinu\" -> \"$newNinu\" toujours invalide ($ninuError)";
    } elseif (count($ninuTargets[$newNinu]) > 1) {
        $reasons[] = "NINU : \"$oldNinu\" -> \"$newNinu\" entrerait en collision avec le(s) compte(s) #"
            . implode(', #', array_diff($ninuTargets[$newNinu], [$id]));
    }
    if ($phoneError) {
        $reasons[] = "Téléphone : \"$oldPhone\" -> \"$newPhone\" toujours invalide ($phoneError)";
    } elseif (count($phoneTargets[$newPhone]) > 1) {
        $reasons[] = "Téléphone : \"$oldPhone\" -> \"$newPhone\" entrerait en collision avec le(s) compte(s) #"
            . implode(', #', array_diff($phoneTargets[$newPhone], [$id]));
    }

    if (!empty($reasons)) {
        $unresolved[$id] = ['name' => $u['name'], 'reasons' => $reasons];
        continue;
    }

    if ($oldNinu !== $newNinu || $oldPhone !== $newPhone) {
        $toUpdate[$id] = ['name' => $u['name'], 'ninu' => $newNinu, 'phone' => $newPhone];
    }
}

echo "=== Comptes à mettre à jour (" . count($toUpdate) . ") ===\n";
foreach ($toUpdate as $id => $row) {
    echo "  #$id {$row['name']} -> ninu=\"{$row['ninu']}\" phone=\"{$row['phone']}\"\n";
}

echo "\n=== Comptes NON modifiés, à corriger manuellement (" . count($unresolved) . ") ===\n";
foreach ($unresolved as $id => $row) {
    echo "  #$id {$row['name']}\n";
    foreach ($row['reasons'] as $reason) {
        echo "      - $reason\n";
    }
}

/**
 * Prépare la normalisation du téléphone (facultatif, non UNIQUE) d'une table
 * annexe de l'admin. Contrairement à "users", une ligne sans téléphone est
 * ignorée (rien à normaliser) et il n'y a pas de vérification de doublon,
 * puisque la colonne n'est pas contrainte UNIQUE en base pour ces tables.
 */
function preparePhoneUpdates(PDO $pdo, string $table, string $idCol, string $labelCol, string $phoneCol): array
{
    $rows = $pdo->query("SELECT $idCol AS id, $labelCol AS label, $phoneCol AS phone FROM $table ORDER BY $idCol")->fetchAll();

    $toUpdate   = [];
    $unresolved = [];

    foreach ($rows as $row) {
        $id       = (int) $row['id'];
        $oldPhone = (string) ($row['phone'] ?? '');

        if (trim($oldPhone) === '') {
            continue; // Téléphone facultatif : rien à normaliser.
        }

        $newPhone   = normalize_phone($oldPhone);
        $phoneError = validate_phone($newPhone);

        if ($phoneError) {
            $unresolved[$id] = [
                'label'   => $row['label'],
                'reasons' => ["Téléphone : \"$oldPhone\" -> \"$newPhone\" toujours invalide ($phoneError)"],
            ];
            continue;
        }

        if ($oldPhone !== $newPhone) {
            $toUpdate[$id] = ['label' => $row['label'], 'phone' => $newPhone];
        }
    }

    return [$toUpdate, $unresolved];
}

$auxTables = [
    'financial_institutions' => ['idCol' => 'id', 'labelCol' => 'name', 'phoneCol' => 'phone', 'titre' => 'Institutions financières'],
    'suppliers'               => ['idCol' => 'id', 'labelCol' => 'name', 'phoneCol' => 'phone', 'titre' => 'Fournisseurs'],
    'bnc_agences'             => ['idCol' => 'id', 'labelCol' => 'nom',  'phoneCol' => 'telephone', 'titre' => 'Agences BNC'],
];

$auxUpdates = [];
foreach ($auxTables as $table => $cfg) {
    [$tblToUpdate, $tblUnresolved] = preparePhoneUpdates($pdo, $table, $cfg['idCol'], $cfg['labelCol'], $cfg['phoneCol']);
    $auxUpdates[$table] = ['column' => $cfg['phoneCol'], 'idCol' => $cfg['idCol'], 'toUpdate' => $tblToUpdate];

    echo "\n=== {$cfg['titre']} à mettre à jour (" . count($tblToUpdate) . ") ===\n";
    foreach ($tblToUpdate as $id => $row) {
        echo "  #$id {$row['label']} -> {$cfg['phoneCol']}=\"{$row['phone']}\"\n";
    }

    echo "\n=== {$cfg['titre']} NON modifiées, à corriger manuellement (" . count($tblUnresolved) . ") ===\n";
    foreach ($tblUnresolved as $id => $row) {
        echo "  #$id {$row['label']}\n";
        foreach ($row['reasons'] as $reason) {
            echo "      - $reason\n";
        }
    }
}

$totalUpdates = count($toUpdate);
foreach ($auxUpdates as $info) {
    $totalUpdates += count($info['toUpdate']);
}

if (!$apply) {
    echo "\nAperçu seulement — aucune donnée modifiée.\n";
    echo "Relancez avec --appliquer pour effectuer ces mises à jour.\n";
    exit(0);
}

if ($totalUpdates === 0) {
    echo "\nRien à appliquer.\n";
    exit(0);
}

$pdo->beginTransaction();
try {
    if (!empty($toUpdate)) {
        $stmt = $pdo->prepare('UPDATE users SET ninu = :ninu, phone = :phone WHERE id = :id');
        foreach ($toUpdate as $id => $row) {
            $stmt->execute(['ninu' => $row['ninu'], 'phone' => $row['phone'], 'id' => $id]);
        }
    }

    foreach ($auxUpdates as $table => $info) {
        if (empty($info['toUpdate'])) {
            continue;
        }
        $stmt = $pdo->prepare("UPDATE $table SET {$info['column']} = :phone WHERE {$info['idCol']} = :id");
        foreach ($info['toUpdate'] as $id => $row) {
            $stmt->execute(['phone' => $row['phone'], 'id' => $id]);
        }
    }

    $pdo->commit();
    echo "\n" . $totalUpdates . " ligne(s) mise(s) à jour avec succès.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    echo "\nÉchec : aucune donnée n'a été modifiée (" . $e->getMessage() . ").\n";
    exit(1);
}
