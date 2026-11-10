# Diagnostic Inhemeter i100 (COM5) + DCU IHM-5000 (192.168.202.159)

Date de la session : **2026-11-09**  
Dossier local : `C:\Users\Daniel\Documents\2025 - Personnel\Projets\SUBNETCONGO\elearning\inhemeter_diagnostic`

---

## 1. Objectifs

1. Déterminer si le compteur Inhemeter relié en optique sur **COM5** est **G3-PLC** ou **RF**.
2. Analyser le DCU Inhemeter à l'adresse **192.168.202.159** :
   - confirmer qu'il utilise bien du **G3-PLC** ;
   - expliquer pourquoi il **ne distribue pas d'adresses IPv6** aux compteurs ;
   - vérifier s'il **écoute / relève des compteurs Landis+Gyr**.
3. Regrouper dans un dossier local tous les scripts, logs, fichiers de test et l'historique des recherches.

---

## 2. Environnement de travail

| Élément | Valeur |
|---|---|
| Poste | Windows 10/11, PowerShell 5.1 |
| Python | 3.13.13 |
| Dépendances | `pyserial`, `gurux_dlms` |
| Port compteur | `COM5` = « USB Serial Port » (sonde optique) |
| Accès DCU | SSH `root@192.168.202.159` (mot de passe fourni par l'utilisateur) via `plink.exe` |
| Empreinte SSH DCU | `SHA256:Or4HdVQDGgN1NhG63RGZN2SsJ3pdBcipRMbozpowaY8` |
| Redis DCU | `127.0.0.1:6239`, mot de passe fourni par l'utilisateur (`inheroot`) |
| Bus D-Bus système | services `inhe.dcu.*`, `bus.comm.traff` |

> Les mots de passe ne sont volontairement pas recopiés en clair dans ce fichier.

---

## 3. Partie compteur — Inhemeter sur COM5

### 3.1 Identification

- Identification optique : `/IHM5\2Inhe Meter`
- Numéro de série : `037990133045`
- Firmware : `1.0.0.2.0.255` → `DKP2-4E-26012401`
- Info module : `0.0.96.74.6.255` → `SPGH3-211020`
- Version module : `0.0.96.74.8.255` → `06`
- Nom logique COSEM `0.0.42.0.0.255` : **protégé** (`PROTEGE`, code 3)
- Nameplate / profils (`0.0.94.91.x`, `1.0.96.50.x`, `0.0.96.1.1/1.2/1.4`) : **absents** ou non lisibles

### 3.2 Associations et clients DLMS

Objets de la classe 15 (associations) observés :

| Objet | Client | Résultat |
|---|---|---|
| `0.0.40.0.0` | 18 | lecture OK (vue publique) |
| `0.0.40.0.1` | 16 | OK |
| `0.0.40.0.2` | 97 | OK |
| `0.0.40.0.3` | 18 | OK |
| `0.0.40.0.4` | 102 | OK |
| `0.0.40.0.5` | 96 | OK |

Tests d'association :

| Client | Auth | Résultat |
|---|---|---|
| 18 | LOW | **OK** — 2055 objets |
| 16 | NONE | OK — 41 objets |
| 96 | NONE | rejeté |
| 97 | NONE | rejeté |
| 102 | NONE | rejeté |
| 16 | LOW | rejeté |
| 96 | LOW | **OK** — 22 objets |
| 97 | LOW | **OK** — 2044 objets |

### 3.3 Vue client 18 (2055 objets)

Répartition par classe :

| Classe | Nb |
|---|---|
| 1 (Data) | 101 |
| 3 (Register) | 1373 |
| 4 (Extended register) | 540 |
| 7 (Profile generic) | 18 |
| 8 (Clock) | 1 |
| 9 (Script table) | 2 |
| 11 (Special days) | 3 |
| 15 (Association) | 6 |
| 17 (SAP assignment) | 1 |
| 18 (Image transfer) | 1 |
| 20 (Activity calendar) | 2 |
| 23 (IEC HDLC setup) | 2 |
| 64 (Security setup) | 4 |
| 70 (Disconnect control) | 1 |

**Aucune classe de communication** n'est exposée :

- G3-PLC : classes **90/91/92**, **160-162** → absentes (`object-undefined`, code 4)
- Wi-SUN : **95-98** → absentes
- Couche IP : **41-48** → absentes
- PRIME : **80-86** → absentes
- S-FSK : **50-56** → absentes

Balayage complémentaire des objets `0.{0,1}.25..29.x.0.255` (x = 0..15) : **aucun objet trouvé** (0 hit).

### 3.4 Vue client 96 (22 objets)

- `0.0.96.1.0.255` = `037990133045`
- `0.0.96.1.8.255` = `[48]`
- `0.0.96.74.6.255` = `SPGH3-211020`
- `0.0.96.74.8.255` = `06`
- `0.0.128.0.30.255` = `1`
- `1.0.96.50.15.255` = `2`
- `1.0.96.50.80.255` = `[4, 0]`
- `1.0.96.50.82.255` = `[6,0,5,0,7,0,8,0,222,3,11,0,3,0,1,0]`

### 3.5 Conclusion « G3-PLC ou RF ? »

- Le **dump DLMS ne contient aucune classe de communication** (ni G3-PLC, ni Wi-SUN, ni PRIME, ni S-FSK, ni IPv6). Le port optique ne permet donc pas de lire directement la pile radio/PLC du compteur.
- L'identifiant module **`SPGH3-211020`** et la documentation constructeur pointent vers un module **G3**.
- Recherche web : le modèle **Inhemeter i100 (DDZ1513)**, matériel `INHE-SPGR5-60`, firmware `3.07.01`, est certifié **G3-Hybrid PLC + RF** :
  - certificat `G3.2607.813.2.C7` (profil FCC)
  - certificat `G3.2607.814.2.A7` (profil CENELEC A)
  - plateforme `REL-G3PLC-CPX3+TRG` (`G3.2312.588.1.C7`), datée du 16/07/2026
  - autres modèles de la gamme : i210/i310 (`SPGR5-30`), IHM-5000 (`CPGR5-40`)
- **Preuve d'exploitation** : ce compteur (`037990133045`) est enregistré sur le réseau PLC du DCU avec **TEI 2**, `protocol 2`, `comtype PLC`, dans un PAN dont le module est un module **G3/HPLC** (voir partie 4). Sa voie PLC est donc bien du **G3-PLC**.
- La partie **RF** (hybride) n'est ni visible ni utilisée : le canal RF du DCU (`local-2`) est vide et le mode mixte est désactivé (`mixed_switch = off`).

**Réponse courte** : compteur **G3-Hybrid** (PLC G3 + RF), exploité ici en **G3-PLC** ; rien n'indique une exploitation RF.

---

## 4. Partie DCU — IHM-5000 (192.168.202.159)

### 4.1 Système et versions

- Linux `IH 4.1.15`, ARMv7l, Buildroot 2015.08.1
- `externalModel` : `IHM-5000`
- `internalModel` : `DJGA70`
- `dcu_sn` : `260600010`
- `serialNumber` : `123456789`
- `dcu_sw` : `v2.3.3.302-a72de89-2026-07-02 17:48:24`
- Applications :
  - `traff` `v1.00.0-0a9121c`
  - `task` `v0.0.10-9d9b764`
  - `metering` `v1.0.0-502ba08`
  - `plugin_module` `v0.0.1-bda7ab6`
  - `html` `v2.0.7-5dfe27e`
  - `ftp_dev` `v0.0.2-34ecbd7`
- Températures : interne ≈ 42,2 °C, CPU ≈ 67,4 °C
- Uptime au moment du diagnostic : 0 j 20 h 16 min

### 4.2 Pile PLC : c'est bien du G3-PLC

- `plugin.log` :
  - `GetModuleTypeCB() L968: pTypeStr:HPLC.`
  - `GetPanIDCB() L1589: GetPanIDCB: 0010`
- `getDownLinkInfo_Channel` :
  ```json
  {"modules":["local-1","local-2"],"details":[
    {"name":"local-1","manufacturer":"INHE","sf":"V0.0.03-260616","ChannelType":"HPLC","PAN_ID":"0010","online":3},
    {"name":"local-2","manufacturer":"","sf":"","ChannelType":"","PAN_ID":"","online":0}
  ]}
  ```
- Binaire `plugin_module` : présence de nombreux symboles G3 (`G3_NetTopo`, `g3_topo`, `g3_panid`, `G3 Module version`, `HPLC Band`, `LME-HPLC-HRF moudle reset`, `dual Module version`).
- Le module PLC répond en **Q/GDW 1376.2** sur l'UART : trames `68 … 16`, AFN `0x10`, F21 = topologie, F31 = phases.
- Le module physique est déclaré **HPLC** (donc PLC haut débit = G3), pas RF.
- `/home/work/dcu_config.json` mentionne encore `G3/PLC` et `RF` sur `/dev/ttymxc2`, mais ce fichier est **obsolète** : le PLC réel est sur `/dev/ttymxc1` (`/home/plc/plc.conf`), et `ttymxc2` est occupé par `hciattach_quectel ... bcm43xx` (Bluetooth).
- `rss_g3/` (pile Renesas) et `ti_g3/` existent mais **ne tournent pas** : `/tmp/renesas` absent, `p3762_chn` non lancé, `ti_g3` vide. La pile G3 est embarquée dans le **module coprocesseur** (`module_name = dual-mod`, `module_software_ver = 2.1.0`, `coprocessor_version = 20250819`), piloté par `plugin_module`.

### 4.3 Topologie du réseau PLC

Topologie persistée et rafraîchie toutes les minutes :

```
topo table size: 3
meter 000260600010  tei:0 pco_tei:0 level:0 role:4 link_type:0 module_type:0 phase:07   (DCU)
meter 037990133045  tei:2 pco_tei:1 level:1 role:1 link_type:0 module_type:0 phase:10   (compteur COM5)
meter 037990133102  tei:3 pco_tei:1 level:1 role:1 link_type:0 module_type:0 phase:10   (autre compteur)
```

- Rôles : `4` = coordinateur (DCU/CCO), `1` = compteur.
- Les deux compteurs sont des **enfants directs** du DCU (`pco_tei = 1`, `level = 1`).
- `protocol = 2`, `comtype = PLC` pour les deux compteurs.
- `NodeMaptbl` (SQLite `plugin_param.db`) : **0 ligne** — la table MAC/deviceDI n'est pas utilisée dans ce mode.

Liste « online » (`getOnlineList`) :

| Index | Meter ID | Dernière mise à jour |
|---|---|---|
| 1 | `000260600010` (DCU) | 2026-11-09 07:12:00 |
| 2 | `037990133102` | 2026-11-09 07:09:49 |
| 3 | `037990133045` | 2026-11-09 08:41:07 |

### 4.4 Redis (port 6239)

Clés principales (25 clés) :

| Clé | Type | Valeur |
|---|---|---|
| `LIB_DLMSMETER` | string | `V2.5.0.230818` |
| `module_name` | string | `dual-mod` |
| `module_software_ver` | string | `2.1.0` |
| `coprocessor_version` | string | `20250819` |
| `module_max_node_num` | string | `1000` |
| `workmode` | string | `{"mode":"dcu"}` |
| `mixed_switch` | string | `{"mixed":"off"}` |
| `PLC_INSERT_S` | string | `IN` |
| `GPRS_INSERT_S` | string | `IN` |
| `whitelist_switch` | string | `{"switch":"off"}` |
| `network_scale` | string | `{"network_scale":1000}` |
| `networking_time` | string | `{"networking_time":30}` |
| `plugin_static` | string | `{"sndnum":0,"rcvnum":0,"snd_time":"","rcv_time":""}` |
| `coprocessor_temp` | string | `42.2` |
| `plus_online_list` | set | `037990133102`, `000260600010`, `037990133045` |
| `plus_routine` | zset | 3 enregistrements (voir ci-dessous) |

`plus_routine` (ZRANGE `0 -1`) :

```json
{"index":0,"meterno":"037990133102","node":"037990133102","farther":"000000000000","ipv6":"","status":"online","datetime":"2026-11-09 07:09:49","region":"null","phase":"null","comtype":"PLC"}
{"index":1,"meterno":"037990133045","node":"037990133045","farther":"000000000000","ipv6":"","status":"online","datetime":"2026-11-09 08:41:07","region":"null","phase":"null","comtype":"PLC"}
{"index":2,"meterno":"000260600010","node":"000260600010","farther":"000260600010","ipv6":"","status":"online","datetime":"2026-11-09 07:12:00","region":"null","phase":"null","comtype":"PLC"}
```

Points clés : `node` = numéro de série (pas une adresse MAC), `farther = 000000000000` pour les compteurs, **`ipv6` toujours vide**, `comtype = PLC`.

### 4.5 Pourquoi aucune distribution IPv6 ?

Constats :

1. `/proc/net/if_inet6` ne contient **aucune adresse IPv6 globale** et **aucune interface 6LoWPAN** (`6lowpan`) n'existe. Aucun module noyau 6LoWPAN n'est chargé (seul `ifxahd`, driver du modem PLC, est présent).
2. Les trames échangées entre le DCU et le module PLC sont des trames **Q/GDW 1376.2 sur UART** (AFN 0x10), pas des paquets IPv6. L'adressage des compteurs se fait par **PAN ID + TEI** (`0010`, TEI 2 et 3) et par **node ID** (= n° de série).
3. Le champ `ipv6` de chaque nœud (`plus_routine`) est **vide** pour les trois nœuds.
4. Les chaînes IPv6 de `traff` concernent uniquement l'**uplink** (`Eth IPV6 Setup`, `Server IPV6 Setup`, `ipv6ServerIp`, `ipv6ListeningPort`, `eth_ipv6_gw`) ou des fonctions G3 non activées (`deal_send_g3_ipv6`, `plc_g3_ipv6_task`, `set_term_ipv6_para`).
5. L'interface web elle-même prévoit le cas : commentaire dans le JS « some meter solutions don't have ipv6 address » (masquage ajouté le 2024-10-30).
6. `getUpLinkInfo_ETH` : ETH1 avec IP `192.168.202.159`, masque `255.255.0.0`, MAC `00:E0:66:11:00:10`. L'IPv6 n'est pas configuré côté uplink non plus.

**Conclusion** : ce firmware DCU utilise le module G3 en mode **transport 1376.2 / DLMS** (adressage TEI), pas en mode **routeur 6LoWPAN**. Il n'embarque ni pile IPv6 downlink ni serveur DHCPv6/ND pour le réseau PLC, donc il ne distribue pas d'adresses IPv6 aux compteurs. Les réglages IPv6 présents dans l'IHM ne concernent que la liaison montante (Ethernet/GPRS vers le serveur).

### 4.6 Le DCU écoute-t-il des compteurs Landis+Gyr ?

- Le réseau PLC ne contient **que des compteurs Inhemeter** : `037990133102`, `037990133045`, plus le DCU lui-même. **Aucun compteur Landis+Gyr.**
- Aucune chaîne `LGZ`, `Landis`, `L+G`, `Gyr` dans les binaires (`metering`, `task`, `traff`, `plugin_module`, `daemon`, `libDataFormat.so`) ni dans les logs décompressés (`/tmp/ram_log/archive/traff/*.gz`).
- Le dossier `/opt/home/work/dlms/profile` (profils compteurs) est **vide** ; seuls `libDataFormat.so` et `libLogWriter.so` sont présents. Aucun profil constructeur Landis+Gyr n'est installé.
- `metering_para.db` / `meterdb.bin` / `online_manage.bin` / `ex_para.bin` sont binaires/chiffrés et ne contiennent aucune chaîne exploitable.
- Le canal RF `local-2` est vide (`online:0`), `mixed = off` : le DCU n'écoute pas non plus un réseau RF où des compteurs LGZ pourraient se trouver.

**Conclusion** : le DCU ne relève et n'écoute **aucun** compteur Landis+Gyr actuellement. Un support DLMS générique existe (libDataFormat, OBIS), mais il n'y a ni profil LGZ, ni nœud LGZ associé, ni canal RF actif.

### 4.7 Configuration réseau du DCU (`/opt/home/work/con_config.cfg`)

```
fep_addr = 153.67.138.227      fep_port = 47610
local_addr=123.88.6.218        local_port=3761
plc_channel=2                  plc_identify=1   plc_interval=5
eth_addr=192.168.202.159       gw_addr=192.168.202.254   eth_mask=255.255.0.0
listen_port=10000              dhcp_enable=0
mac_addr=00:e0:66:11:00:10     serial_number=123456789
timezone=Etc/GMT-2             A_area_code=2606   A_ter_addr=10
workmode=1                     net_scan_mode=0
```

Autres fichiers :

- `/home/plc/plc.conf` → `Uart_Name = /dev/ttymxc1`
- `/home/work/rss_g3/config/g3_conf.conf` → paramètres CA/FCC, `node_limit_rc=31`, `node_limit_acc_rc=501`, `white_list_open=0`, `up_jion_enable=1`
- `/home/work/rss_g3/version.txt` → `renesas_dcu_both_17090501_v1.0.5-20170906`
- `/tmp/work/g3_panid_mode_config.bin` → `0x01`
- `/home/work/ex_para.bin` contient une URL SFTP interne (identifiants **redactés** ici)
- `submodule_info.json` : versions cohérentes avec `getTermInfo`

### 4.8 D-Bus

Services présents :

```
inhe.dcu.downlink   inhe.req.task       com.example.CAutoAgent
inhe.req.ftp        inhe.dcu.metering   bus.comm.traff
inhe.dcu.traff
```

- Les appels de méthodes directs fonctionnent (`getTermInfo`, `getOnlineList`, `getDownLinkInfo_Channel`, etc.).
- `gdbus introspect` sur `inhe.dcu.traff` et `inhe.dcu.downlink` **time out**.
- `getDownLinkInfo_Communication` **ne répond pas** (timeout 60 s) : méthode non implémentée ou bloquée dans ce firmware.

---

## 5. Chronologie des essais

| Heure | Script | Objet | Résultat |
|---|---|---|---|
| 11:03 | `test_g3plc_rf_approfondi.py` | scan complet compteur | rapport généré |
| 11:18 | `scan_view.py` | classes, associations, mots-clés | 2055 objets, aucune classe comm |
| 11:33 | `client_scan.py` | test clients 16/96/97/102 | 18 LOW, 96 LOW, 97 LOW OK |
| 11:41 | `client96.py` | dump vue client 96 | `SPGH3-211020` |
| 11:51 | `nameplate.py` | nameplate / profils | absents |
| 12:08 | `dcu_explore.sh` | arborescence DCU | OK |
| 12:09 | `dcu_config.sh` | `plc.conf`, `dcu_config.json`, `g3_conf` | PLC sur `ttymxc1` |
| 12:10 | `dcu_pan.sh` | PAN ID, mode | `0010`, mode `0x01` |
| 12:11 | `dcu_web.sh` / `dcu_web2.sh` | code web (PHP/JS) | menu, IPv6, commentaire 2024-10-30 |
| 12:13 | `dcu_redis.sh` | dump Redis | 25 clés |
| 12:13 | `dcu_dbus.sh` / `dcu_dbus2.sh` | introspection D-Bus | timeouts |
| 12:15 | `dcu_json.sh` | configs JSON | modèles, versions |
| 12:16 | `dcu_call.sh` | appels D-Bus | infos système |
| 12:17 | `dcu_info.sh` | `getTermInfo`, `getSystemPara` | OK |
| 12:18 | `dcu_deep.sh` + `dcu_deep1..3.log` | binaires, strings | G3/HPLC/3762 |
| 12:22 | `dcu_lg*.sh` | recherche Landis+Gyr | rien |
| 12:25 | `dcu_proto.sh` | protocole PLC | 376.2, G3 |
| 12:26 | `dcu_ipv6.sh` | IPv6 / 6LoWPAN | aucun |
| 12:28 | `dcu_plus.sh` | `plus_routine` | liste (puis découverte zset) |
| 12:29 | `dcu_redis2.sh` / `dcu_zset.sh` | types et valeurs Redis | zset `plus_routine`, set `plus_online_list` |
| 12:31 | `dcu_parambin.sh` | `ex_para.bin`, `manu_item_statedb.bin` | binaires/chiffrés |
| 12:33 | `dcu_final.sh` | `getDownLinkInfo_Communication`, logs LGZ | timeout, aucun LGZ |
| 12:35 | `dcu_logs2.sh` | logs décompressés | aucun LGZ |
| 12:36 | `dcu_modtype.sh` | type module | `HPLC`, `G3 Module version` |
| 12:37 | `dcu_fw.sh` | firmware G3 | pas de `g3_fw_info.bin` |
| 12:38 | `dcu_tree.sh` | arborescence `dlms`/`rss_g3` | profils vides |
| 12:39 | `dcu_dlms.sh` | `libDataFormat`, profils | aucun profil LGZ |

---

## 6. Reproduire les manipulations

### Compteur (depuis le PC Windows)

```powershell
python .\meter\scripts\test_g3plc_rf_approfondi.py
python .\meter\scripts\scan_view.py
python .\meter\scripts\client_scan.py
python .\meter\scripts\client96.py
python .\meter\scripts\nameplate.py
```

### DCU (plink, scripts envoyés par `-m`)

```powershell
plink -ssh -pw <mot_de_passe> -batch `
  -hostkey "SHA256:Or4HdVQDGgN1NhG63RGZN2SsJ3pdBcipRMbozpowaY8" `
  -m .\dcu\scripts\dcu_final.sh root@192.168.202.159
```

> Prévoir 2–3 s entre deux connexions : `plink` renvoie parfois
> `FATAL ERROR: Server refused to start a shell/command`.

### Redis sans `redis-cli`

```sh
# Connexion TCP brute + RESP, longueurs calculées automatiquement
cmd() { printf '*%d\r\n' $#; for a in "$@"; do printf '$%d\r\n%s\r\n' "${#a}" "$a"; done; }
{ cmd AUTH <mot_de_passe>; cmd ZRANGE plus_routine 0 -1 WITHSCORES; } | nc -w 3 127.0.0.1 6239
```

> Piège rencontré : longueur RESP erronée (`$7` au lieu de `$8`) → `-ERR invalid password`.

### D-Bus

```sh
dbus-send --system --print-reply --reply-timeout=60000 \
  --dest=inhe.dcu.downlink /inhe/dcu/downlink \
  inhe.dcu.downlink.getDownLinkInfo_Channel
```

---

## 7. Contenu du dossier

```
inhemeter_diagnostic/
├── README.md                        ce fichier
├── meter/
│   ├── scripts/                     scripts Python DLMS
│   ├── logs/                        logs d'exécution
│   └── reports/                     rapport texte + JSON du compteur
└── dcu/
    ├── scripts/                     scripts shell exécutés sur le DCU
    └── logs/                        sorties correspondantes
```

---

## 8. Limites et prochaines étapes

1. **Voie RF** : le dump DLMS ne l'expose pas. Pour la confirmer, il faudrait un module RF/analyseur, ou activer `local-2` côté DCU (actuellement vide). À ce stade, seule la voie PLC est prouvée.
2. **Type exact du module PLC** : `GetModuleTypeCB` renvoie `HPLC` ; les symboles du binaire confirment la pile G3, mais la version détaillée du firmware G3 n'est pas journalisée (`g3_fw_info.bin` absent).
3. **Landis+Gyr** : pour un support éventuel, il faudrait installer des profils DLMS (dossier `dlms/profile` vide), configurer les OBIS et vérifier la whitelist / l'auto-join du module.
4. **IPv6** : pas de pile 6LoWPAN côté Linux. Une distribution IPv6 nécessiterait un firmware DCU en mode routeur 6LoWPAN, ce que ce modèle n'expose pas.
5. `getDownLinkInfo_Communication` reste sans réponse : à creuser uniquement si un accès bas niveau au module est nécessaire.

---

## 9. Session 2026-11-09 (soir) — DCU depuis le domicile (lecture seule)

### 9.1 Accès / état
- DCU joignable en SSH : `192.168.202.159` (`IH`), hostkey inchangée. Services actifs : `traff`, `plugin_module`, `metering`, `task`, `daemon/dropbear`. **Aucune modification effectuée sur le DCU.**
- Tests optique COM5 **non disponibles** depuis le domicile ; script client 1 stoppé localement.

### 9.2 Nœuds réellement vus par le DCU (pas de Landis+Gyr)
Preuves `plus_online_list`/`plus_routine` (Redis 6239), topo `plugin`, joins `traff/plugin` (`06H-F1`, `mod_node_join`, `channel-local-2/PLC`) — logs `dcu/logs/dcu_nodes_seen.log`, `dcu_nodes_db.log` :

| Nœud | Rôle / interprétation | Preuve |
|---|---|---|
| `000260600010` | DCU (`dcu_sn`) | topo/routine |
| `037990133045` | Inhemeter i100 (confirmé optique COM5 : `/IHM5…`, `DKP2-4E-26012401`, `SPGH3-211020`) | Redis + DLMS optique |
| `037990133102` | Compteur sur le même PAN PLC, format/préfixe cohérent Inhemeter (`03799…`), **même comportement** que 045 (TEI 3, `protocol 2`, `comtype PLC`) | Redis + logs join/topo |

**Conclusion** : **aucun compteur Landis+Gyr (`LGZ`/Landis/L+G) observé** côté DCU (binaries/logs/DB/Redis). `LGZ1020060342611` présent uniquement dans des données SmartTools locales du PC ≠ nœud vu par ce DCU. `NodeMaptbl` vide ; `meterdb/itemdb/metering_para` sans autres IDs exploitables.
**Pour être certain à 100 % sur `037990133102`** : lire à distance via DCU/SmartTools ou optique `0-0:96.1.0.255` (serial), `1-0:0.2.0.255` (FW), `0-0:42.0.0.255` (logical device name si lisible). Un compteur Landis expose typiquement un identifiant/fabricant/logical name différent.

### 9.3 PSK G3-PLC côté DCU (sécurité réseau G3, distincte DLMS)
- Config : `/opt/home/httpd/html/lit_down_config.cfg` contient `plc_psk` / `plc_psk_len` (16 octets). **Valeur sensible non recopiée ici** (voir extraction locale contrôlée `dcu/logs/dcu_psk_findings_2026-11-09.md`).
- Preuve de chargement `traff` (2026-11-09 17:02:23/55, `/tmp/ram_log/traff/traff.log`) : `get_lit_down_config: plc_psk_tmp/plc_psk_len` puis `init:plc_psk=<hex>` → PSK chargée par `traff` au démarrage.
- Chaîne d’application (indices binaires/logs) : `traff` (`RSS:set psk ok`, `g3_psk`, `mod_g3_psk`, `set/get plc_psk`) → `plugin_module` enregistre D-Bus `mod_g3_psk` (`plugin.log`: `[method]g3 psk config`) → `rss_g3/renesas_dcu` (`psk_set/psk_get`, `adp psk`, `p3762 set psk info/fail/ok`). Application au **module/pile G3 (3762/Renesas)**.
- **Limites observées** : pas d’appel `mod_g3_psk` visible dans logs courants/archives ; messages `set psk ok/need reset` présents en chaînes `traff` mais non reloggués ; **pas de log runtime Renesas/G3** trouvé ; introspection D-Bus `inhe.dcu.downlink` timeout. On confirme le chargement/config DCU, pas le détail par nœud.
- Redis : **aucune clé PSK** ; `con_config.cfg`/`g3_conf.conf` sans PSK. **PSK G3 ≠ clés DLMS HLS/GMAC (EK/AK/system title, client 1).**

### 9.4 Credentials DLMS / SmartTools (local, PC) — pas de clé HLS client 1 trouvée en clair
- `C:\Users\Daniel\AppData\Local\Programs\SmartTools3` connecté au DCU `192.168.202.159:10000` (`profile/InfoFile.txt`, modèle `DM_DJGZ80_INHE`).
- Modèle `DM_i100_UMG` : profils GPRS/G3-PLC(DCU)/485/IR/G3-PLC local/RF ; Wrapper/HDLC par défaut client **18, LLS `12345678`, security/mech `15:0`**. Security Setup 14.1-14.9 au menu, mais **pas de `Param/.../037990133045/14.*.xml`** ; `15.*` sans champs cipher/HLS/AK/EK.
- Historique SmartTools `DevModel/history.db` : secrets d’association `12345678` (DJGZ80) + profils i100 045 ; **pas d’EK/AK/system title/cipher** en clair pour 045. `BackUp/.../DM_i100_UMG.db` contient `HLS` en binaire (non extrait). `authority.db` non exploitable.
- **Conclusion** : accès i100 observé via **client 18 LLS** ; credentials **HLS/GMAC client 1** (i100 G0) non récupérés. À valider demain à l’optique (COM5 stable) avec paramètres réels (Security Setup/auth/system title/AK/EK) ou export SmartTools masqué.

### 9.5 Test client 1 i100 G0 (optique, interrompu)
- Cible : `037990133045`, COM5. Passe rapide (~2 min, `meter/logs/client1_2min.log`) : `NONE ''` rejeté ; `LOW 1234567,00000000,0000000,0000000000000000,1111111,11111111,22222222` rejetés (`mechanism not recognized`/rejet). **À partir de `33333333`**, COM5 a eu des erreurs `ClearCommError/PermissionError` puis port introuvable → fin de passe (incluant autres mots de passe/`HIGH`) **non concluante**. **Aucun match client 1 validé.** Reprise demain optique avec port stable.

Traces : `dcu/logs/dcu_psk_chain.log`, `dcu_meter_creds.log`, `dcu_meter_bins2.log`, `dcu_nodes_seen.log`, `meter/logs/client1_2min.log`, scripts `smarttools_inspect_db.py`/`smarttools_history.py`.

---

## 10. Accès distant DCU sans Ethernet — 4G Airtel, IP dynamique, APN public (Internet), DCU connecté uniquement au serveur

### 10.1 Constat DCU (lecture seule)
- Écoutes `0.0.0.0` : **SSH 22** (dropbear), **23 telnet**, **21 ftp**, **80 http**, **6239 redis** (sensible), **10000 `traff` (SmartTools/maintenance DCU)**, 111 rpcbind ; `127.0.0.1:6666` local update. Aucune règle `iptables` visible. Preuve : `dcu/logs/dcu_remote_access*.log`, `dcu_jump_options.log`.
- `con_config.cfg` : `listen_port=10000`, `fep_addr=153.67.138.227 fep_port=47610`, `local_port=3761`, `fep_channel=3`. Modem/cellulaire présent (`/dev/ttyUSB*`, `/dev/ppp`) mais **`apn=` vide** et `traff.log` montre boucles d’échec/reset GPRS (pas de data 4G active).
- Outils sortants disponibles : `ssh`/`dbclient`, `scp`, `nc`, `wget`, `busybox`, `crontab` ; **pas** `autossh/openvpn/wg/socat/stunnel`.

### 10.2 Principe
Avec IP 4G dynamique / CGNAT (APN public), **connexion entrante impossible** en général. Le **DCU doit initier une connexion sortante** (comme vers le FEP/serveur). Passer par un **bastion/serveur à IP/DNS fixe** que vous contrôlez.

### 10.3 Option A — tunnel SSH inverse (maintenance shell DCU)
Depuis le DCU vers le bastion (port 22 entrant ouvert sur bastion) :
```sh
ssh -N -T -o ServerAliveInterval=30 -o ExitOnForwardFailure=yes \
  -R 127.0.0.1:2222:127.0.0.1:22 user@BASTION
# (ou dbclient selon options/format de clé Dropbear)
```
Puis sur le bastion : `ssh -p 2222 root@127.0.0.1` (ou compte admin). Pas d’`autossh` : prévoir **watchdog** (cron/init) pour relancer au drop/reboot ; tester en fenêtre de maintenance avec rollback.

### 10.4 Option B — SmartTools via tunnel TCP (port 10000)
SmartTools parle au DCU sur **TCP 10000** (preuve : `SmartTools -> 192.168.202.159:10000`). Tunnel sortant DCU→bastion :
```sh
ssh -N -T -R 127.0.0.1:10000:127.0.0.1:10000 user@BASTION
```
Puis accès opérateur via bastion (ex. saut local `ssh -L 10000:127.0.0.1:10000 user@BASTION` et SmartTools sur `127.0.0.1:10000`, ou exposition bastion **strictement filtrée**). Valider le comportement protocolaire SmartTools dans votre environnement.

### 10.5 Option C — plateforme FEP/head-end existante
Le DCU remonte déjà vers le serveur/FEP (`153.67.138.227:47610`, `fep_channel`). Si le head-end Inhemeter/FEP supporte **maintenance transparente / accès distant inverse**, l’utiliser (option la plus intégrée). Capacités VPN/tunnel explicites non visibles côté DCU ; à confirmer côté serveur.

### 10.6 Prérequis et sécurité
- **Configurer d’abord un APN Airtel Internet fonctionnel** sur le DCU (actuellement vide/non fonctionnel). APN privé M2M + routage/VPN reste préférable si disponible.
- N’exposez pas `23/21/80/6239/10000/22` publiquement ; préférer **clés SSH, binds `127.0.0.1`, filtrage bastion, mots de passe forts**. Redis/telnet particulièrement sensibles.
- Toute persistance (cron/init script) modifie le DCU : sauvegarde config, test, plan de retour arrière. **Rien modifié par cette analyse.**