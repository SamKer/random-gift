# random-gift
Principe du Chapeau Cadeau automatique

## Install

```bash
git clone https://gthub.com/samker/random-gift.git
cd random-gift/
composer install
cp config.dist.yaml config.yaml
```

éditer le fichier de conf avec vos paramètres

puis
- tester:
```bash
./gift run -t
```
- préparer la liste: envoi à tous un pre mail d'avertissement pour valider le liste des volontaire
```bash
./gift run -p
```
- Envoi
```bash
./gift run
```