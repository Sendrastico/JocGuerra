- Joc de la Guerra en PHP -

- Autor/a: Jorge Sendra Noguera (2n DAW Semipresencial IES Jaume II El just)


- Descripció

Joc de cartes de la Guerra amb baralla espanyola (48 cartes) per a jugar al navegador. Es juga contra la màquina i el programa anuncia qui guanya.

- Regles implementades:

Es crea la baralla de 48 cartes (4 pals × 12 valors), es remena i es reparteix: 24 cartes per a cada jugador.
A cada ronda, cada jugador mostra la carta de dalt del seu munt.
La carta més alta s'emporta les dues. Ordre de menor a major: Dos, Tres, Quatre, Cinc, Sis, Set, Vuit, Nou, Sota, Cavall, Rei i As. Els pals no importen.
Si les dues cartes tenen el mateix valor hi ha guerra: cada jugador posa una carta tapada i una altra destapada. La destapada més alta s'emporta totes les cartes en joc. Si tornen a empatar, hi ha una altra guerra.
Si un jugador no té prou cartes per a continuar una guerra, perd la partida.
El guanyador de la ronda posa les cartes guanyades al seu munt. Es remenen abans, per evitar partides infinites.
Final de partida: guanya qui s'emporta totes les cartes. Si es arriba a 1000 rondes, guanya qui té més cartes (o hi ha empat).

- Com es juga:

1. Jugar ronda: juga una sola ronda i mostra les cartes de cada jugador (i les guerres, si n'hi ha).
2. Jugar fins al final: juga totes les rondes seguides fins que algú guanya.
3. Nova partida: remena i reparteix de nou.

- El marcador mostra les cartes que té cada jugador i el número de ronda.

- Com executar-lo

1. Necessites PHP (per exemple, XAMPP o PHP instal·lat).
2. Deixa index.php i la carpeta Cartes (amb els fitxers <Pal> <Valor>.jpg, p. ex. Copes Rei.jpg) a la mateixa carpeta.
3. Des d'eixa carpeta executa php -S localhost:8000 i obri http://localhost:8000 al navegador.

- Fitxers

1. index.php: tot el joc (funcions, lògica i HTML).
2. Cartes/: imatges de la baralla espanyola.
3. README.md: aquest document.
4. GUERRA - memoria.pdf: memòria amb la taula de funcions. (Captures dins de la memòria)

Ampliació

Botó «Jugar fins al final», que juga la partida completa de colp.