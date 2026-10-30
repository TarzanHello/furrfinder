# FurrFinder

Sito pubblico di FurrFinder (https://www.furrfinder.com), il libretto sanitario online di cane e gatto.
Servizio di AF&B S.r.l.s.

## Come è organizzato il repo

```
furrfinder/
├── site/      → TUTTO quello che va caricato sul server (è il sito)
│   ├── .htaccess          redirect, HTTPS, cache
│   ├── *.html             pagine del sito
│   ├── assets/            stile, script, font, immagini, icone
│   ├── pdf/               libretti sanitari da stampare
│   ├── aree-cani/         sezione "Aree cani" (PHP)
│   ├── smarriti/          sezione "Animali smarriti" (PHP)
│   ├── social/            post e reel per i social, divisi per mese
│   │                      (Metricool li prende da qui; Google non li indicizza)
│   ├── sitemap.xml, robots.txt, llms.txt, manifest, favicon
│   └── google98e5….html   verifica di Search Console (non cancellare)
└── README.md, .gitignore → file del repo, NON vanno sul server
```

Regola semplice: **tutto quello che deve stare online va dentro `site/`. Tutto il resto sta fuori.**

## Come caricare il sito con FileZilla

1. Fai il pull del repo, così hai l'ultima versione sul computer.
2. Apri FileZilla e collegati al server.
3. A sinistra (il tuo computer) entra nella cartella `site` del repo.
4. A destra (il server) entra nella cartella principale del sito (di solito `public_html`).
5. A sinistra seleziona **tutto il contenuto** di `site` (non la cartella `site` stessa) e trascinalo a destra.
6. Se FileZilla chiede cosa fare con i file già presenti, scegli **Sovrascrivi** e spunta "Usa sempre questa azione".

I file che iniziano con il punto, come `.htaccess`, possono essere nascosti.
Per vederli: menu **Server** → **Forza la visualizzazione dei file nascosti**.

La cartella `social` va caricata insieme al resto: Metricool scarica post e reel dal link
`https://www.furrfinder.com/social/MESE/nome-file`, per esempio
`https://www.furrfinder.com/social/2026-11/post-01.png`.
I nuovi materiali vanno in una cartella per mese dentro `site/social/` (es. `2026-12`).

## File da non toccare sul server

- `aree-cani/cache/` e `smarriti/cache/`: li crea il sito da solo mentre funziona.
- `smarriti/config.php`: contiene le impostazioni della sezione smarriti.
