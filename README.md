# Bulk Media ZIP Download

Un plugin WordPress qui ajoute une action groupée "Télécharger les fichiers (ZIP)" dans la médiathèque pour télécharger plusieurs médias en une seule archive ZIP.

![Version](https://img.shields.io/badge/version-1.0.1-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-brightgreen.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-red.svg)

## 📋 Description

Ce plugin permet aux utilisateurs WordPress de télécharger facilement plusieurs fichiers médias en une seule archive ZIP directement depuis la médiathèque. Idéal pour les administrateurs qui ont besoin d'exporter des groupes de médias rapidement.

## ✨ Fonctionnalités

- ✅ **Action groupée native** : S'intègre parfaitement dans le menu déroulant des actions groupées de WordPress
- ✅ **Sécurisé** : Vérification des permissions utilisateur (`upload_files` capability)
- ✅ **Gestion intelligente des noms** : Évite les doublons de noms de fichiers dans l'archive
- ✅ **Robuste** : Ignore les fichiers manquants ou inaccessibles sans bloquer le téléchargement
- ✅ **Messages d'erreur clairs** : Notifications admin pour tous les cas d'erreur
- ✅ **Horodatage automatique** : Chaque archive est nommée avec la date et l'heure de création
- ✅ **Nettoyage automatique** : Les fichiers temporaires sont supprimés après téléchargement

## 🚀 Installation

### Installation manuelle

1. Téléchargez le dossier `bulk-media-zip-download`
2. Placez-le dans `/wp-content/plugins/`
3. Activez le plugin depuis le menu **Extensions** de WordPress

### Installation via WP-CLI

```bash
wp plugin activate bulk-media-zip-download
```

## 📖 Utilisation

1. Allez dans **Médias** → **Bibliothèque**
2. Assurez-vous d'être en **vue Liste** (pas en vue Grille)
3. Cochez les médias que vous souhaitez télécharger
4. Dans le menu déroulant **Actions groupées**, sélectionnez **"Télécharger les fichiers (ZIP)"**
5. Cliquez sur **Appliquer**
6. Le téléchargement de l'archive ZIP démarre automatiquement

![Screenshot](https://via.placeholder.com/800x400?text=Screenshot+Coming+Soon)

## 🔧 Prérequis techniques

- **WordPress** : 5.0 ou supérieur
- **PHP** : 7.4 ou supérieur
- **Extension PHP** : `ZipArchive` (généralement incluse par défaut)

### Vérifier la disponibilité de ZipArchive

Vous pouvez vérifier si l'extension est disponible avec ce code PHP :

```php
if (class_exists('ZipArchive')) {
    echo 'ZipArchive est disponible';
} else {
    echo 'ZipArchive n\'est pas disponible';
}
```

## ⚙️ Configuration

Aucune configuration n'est nécessaire. Le plugin fonctionne immédiatement après activation.

### Permissions

Seuls les utilisateurs ayant la capacité `upload_files` peuvent utiliser cette fonctionnalité. Par défaut, cela inclut :
- Administrateur
- Éditeur
- Auteur

## 🗂️ Structure des fichiers

```
bulk-media-zip-download/
├── bulk-media-zip-download.php  # Fichier principal du plugin
└── README.md                     # Documentation
```

## 🛠️ Détails techniques

### Nom des archives

Les archives ZIP sont nommées selon le format :
```
medias-selection-YYYYMMDD-HHMMSS.zip
```

Exemple : `medias-selection-20251210-151530.zip`

### Stockage temporaire

Les fichiers ZIP temporaires sont créés dans :
```
/wp-content/uploads/bmzd-tmp/
```

Ce répertoire est protégé par :
- Un fichier `.htaccess` avec `deny from all`
- Un fichier `index.php` vide pour empêcher le listing

### Gestion des doublons

Si plusieurs fichiers ont le même nom, le plugin ajoute automatiquement un suffixe :
- `image.jpg`
- `image-1.jpg`
- `image-2.jpg`

### Hooks WordPress utilisés

- `bulk_actions-upload` : Ajoute l'action dans le menu déroulant
- `handle_bulk_actions-upload` : Traite l'action sélectionnée
- `admin_notices` : Affiche les messages d'erreur

## 🐛 Gestion des erreurs

Le plugin gère les cas d'erreur suivants :

| Code d'erreur | Message | Cause |
|---------------|---------|-------|
| `unauthorized` | Permissions insuffisantes | L'utilisateur n'a pas la capacité `upload_files` |
| `no_selection` | Aucune sélection | Aucun média n'a été coché |
| `invalid_ids` | IDs invalides | Les identifiants fournis ne sont pas valides |
| `zip_not_available` | ZipArchive manquant | L'extension PHP n'est pas installée |
| `no_valid_files` | Aucun fichier valide | Tous les fichiers sélectionnés sont manquants |
| `zip_creation_failed` | Erreur de création | Problème lors de la génération du ZIP |

## 🔒 Sécurité

- ✅ Vérification des permissions utilisateur
- ✅ Validation et nettoyage des IDs d'attachements
- ✅ Vérification du type de post (`attachment`)
- ✅ Vérification de l'existence physique des fichiers
- ✅ Protection du répertoire temporaire
- ✅ Suppression automatique des fichiers temporaires
- ✅ Utilisation des fonctions WordPress natives (`wp_nonce_url`, `current_user_can`, etc.)

## 🌍 Internationalisation

Le plugin est prêt pour la traduction avec le text domain `bulk-media-zip-download`.

Tous les messages utilisateur utilisent les fonctions WordPress `__()` pour permettre la traduction.

## 📝 Changelog

### Version 1.0.1 (2025-12-10)
- 🐛 **Fix** : Correction du problème de vérification du nonce
- ⚡ **Amélioration** : Génération directe du ZIP sans redirection
- 🔧 **Optimisation** : Simplification du code

### Version 1.0.0 (2025-12-10)
- 🎉 Version initiale
- ✨ Action groupée "Télécharger les fichiers (ZIP)"
- 🔒 Vérification des permissions
- 📦 Génération d'archives ZIP
- 🛡️ Gestion des erreurs

## 🤝 Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :
- Signaler des bugs
- Proposer de nouvelles fonctionnalités
- Soumettre des pull requests

## 📄 Licence

Ce plugin est distribué sous licence GPL-2.0+. Voir [GNU General Public License v2.0](http://www.gnu.org/licenses/gpl-2.0.txt) pour plus de détails.

## 👨‍💻 Auteur

**Custom Development**

## 🙏 Remerciements

Merci à la communauté WordPress pour leur excellent travail sur le système de hooks et d'actions groupées.

---

**Note** : Ce plugin nécessite l'extension PHP `ZipArchive`. Si vous rencontrez des problèmes, contactez votre hébergeur pour vérifier que cette extension est activée.
