#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Test approfondi G3-PLC / RF / HPLC / IP - compteur Inhemeter (DLMS/COSEM via sonde optique)
==========================================================================================
IMPORTANT - ce que prouve (ou non) une adresse IP :
  * G3-PLC fonctionne en IPv6 (6LoWPAN) avec un identifiant EUI-64 (adresse MAC 64 bits)
    et une adresse courte 16 bits + un PAN ID. Une adresse IPv4 NE prouve PAS le G3-PLC.
  * Les vraies preuves G3-PLC sont : classes COSEM 90 / 91 / 92 lisibles (PAN ID,
    adresse courte, compteurs TX/RX, tables voisins/routage), adresse IPv6 (classe 48),
    EUI-64 / adresse MAC (classe 43) ou system title (classe 64, a comparer avec le DCU).

Ce que fait le script, pour chaque client DLMS teste :
  A. Association View : classes presentes, objets CPL/RF/IP declares
  B. Lecture de tous les attributs des objets G3-PLC / G3-RF hybride / Wi-SUN / IP / PRIME / S-FSK
     (valeurs en DECIMAL + annotation IPv4 / IPv6 / EUI-64 / hex)
  C. Test direct des OBIS standard meme s'ils ne sont PAS dans l'Association View, avec
     distinction : LISIBLE / PROTEGE (existe mais acces refuse) / CLASSE DIFFERENTE (existe)
     / ABSENT
  D. Balayage OBIS 0.b.25 a 0.b.29 (communication) pour reperer tout objet qui existe
  E. Identite : EUI-64 / MAC (classe 43), system title (classe 64), IPv4, IPv6
  F. HPLC : pas de classe COSEM standard -> objets proprietaires du fabricant + mots-cles
  G. Verdict par technologie avec niveau de confiance

Usage :
    python test_g3plc_rf_approfondi.py --port COM5 --client 18 --password 12345678
    python test_g3plc_rf_approfondi.py --clients "18:low,16:none,17:low,32:low"

  (--clients : liste client:auth ; auth = low (mot de passe LLS) ou none (public).
   Aucun mot de passe n'est devine : seul le mot de passe fourni est utilise.)

Dependances : pip install pyserial gurux-dlms
"""

import argparse
import ipaddress
import json
import re
import time
from datetime import datetime

import serial
from gurux_dlms import GXDLMSClient, GXReplyData, GXByteBuffer
from gurux_dlms.enums import InterfaceType, Authentication, ObjectType
from gurux_dlms.objects import GXDLMSData

PORT = "COM5"
CLIENT_ID = 18
PASSWORD = "12345678"
UNASSIGNED = 0xFFFF

# ---------------------------------------------------------------------------
# Objets a tester : (famille, description, classe, OBIS, nb max d'attributs)
# ---------------------------------------------------------------------------
PROBES = [
    ("G3-PLC", "MAC layer counters",                 90, "0.0.29.0.0.255", 10),
    ("G3-PLC", "MAC setup",                          91, "0.0.29.1.0.255", 22),
    ("G3-PLC", "6LoWPAN adaptation layer setup",     92, "0.0.29.2.0.255", 19),
    ("G3-PLC-RF", "Hybrid RF MAC counters (a confirmer)", 160, "0.0.29.3.0.255", 12),
    ("G3-PLC-RF", "Hybrid RF MAC setup (a confirmer)",    161, "0.0.29.4.0.255", 25),
    ("G3-PLC-RF", "Hybrid 6LoWPAN (a confirmer)",         162, "0.0.29.5.0.255", 25),
    ("Wi-SUN", "Wi-SUN setup (a confirmer)",         95, "0.0.25.8.0.255", 12),
    ("Wi-SUN", "Wi-SUN diagnostic (a confirmer)",    96, "0.0.25.9.0.255", 12),
    ("Wi-SUN", "RPL diagnostic (a confirmer)",       97, "0.0.25.10.0.255", 12),
    ("Wi-SUN", "MPL diagnostic (a confirmer)",       98, "0.0.25.11.0.255", 12),
    ("IP", "TCP-UDP setup",                          41, "0.0.25.0.0.255", 6),
    ("IP", "IPv4 setup",                             42, "0.0.25.1.0.255", 10),
    ("IP", "MAC address setup",                      43, "0.0.25.2.0.255", 3),
    ("IP", "IPv6 setup",                             48, "0.0.25.7.0.255", 10),
    ("PRIME", "LLC SSCS setup",                      80, "0.0.28.0.0.255", 3),
    ("PRIME", "PHY counters",                        81, "0.0.28.1.0.255", 5),
    ("PRIME", "MAC setup",                           82, "0.0.28.2.0.255", 12),
    ("PRIME", "MAC functional params",               83, "0.0.28.3.0.255", 12),
    ("PRIME", "MAC counters",                        84, "0.0.28.4.0.255", 12),
    ("PRIME", "MAC network admin data",              85, "0.0.28.5.0.255", 8),
    ("PRIME", "Application identification",          86, "0.0.28.6.0.255", 6),
    ("S-FSK", "Phy&MAC setup",                       50, "0.0.26.0.0.255", 14),
    ("S-FSK", "Active initiator",                    51, "0.0.26.1.0.255", 6),
    ("S-FSK", "MAC sync timeouts",                   52, "0.0.26.2.0.255", 6),
    ("S-FSK", "MAC counters",                        53, "0.0.26.3.0.255", 6),
    ("S-FSK", "LLC setup",                           55, "0.0.26.5.0.255", 4),
    ("S-FSK", "Reporting system list",               56, "0.0.26.6.0.255", 4),
]

FAMILY_BY_CLASS = {}
for _fam, _d, _cls, _ln, _n in PROBES:
    FAMILY_BY_CLASS[_cls] = _fam

ATTR_NAMES = {
    90: {2: "tx_data_packet_count", 3: "rx_data_packet_count", 4: "tx_cmd_packet_count",
         5: "rx_cmd_packet_count", 6: "csma_fail_count", 7: "csma_no_ack_count",
         8: "bad_crc_count", 9: "tx_data_broadcast_count", 10: "rx_data_broadcast_count"},
    91: {2: "mac_short_address", 3: "mac_RC_coord", 4: "mac_PAN_id", 5: "mac_key_table",
         6: "mac_frame_counter", 7: "mac_tone_mask", 8: "mac_TMR_TTL",
         9: "mac_max_frame_retries", 10: "mac_neighbour_table_entry_TTL",
         11: "mac_neighbour_table", 12: "mac_high_priority_window_size",
         13: "mac_CSMA_fairness_limit", 14: "mac_beacon_randomization_window_length",
         15: "mac_A", 16: "mac_K", 17: "mac_min_CW_attempts", 18: "mac_cenelec_legacy_mode",
         19: "mac_fcc_legacy_mode", 20: "mac_max_BE", 21: "mac_max_CSMA_backoffs",
         22: "mac_min_BE"},
    92: {2: "adp_max_hops", 3: "weak_lqi_value", 4: "security_level", 5: "prefix_table",
         6: "routing_configuration", 7: "broadcast_log_table_entry_ttl", 8: "routing_table",
         9: "context_information_table", 10: "blacklist_table", 11: "broadcast_log_table",
         12: "group_table", 13: "max_join_wait_time", 14: "path_discovery_time",
         15: "active_key_index", 16: "metric_type", 17: "coord_short_address",
         18: "disable_default_routing", 19: "device_type"},
    41: {2: "TCP_UDP_port", 3: "IP_reference", 4: "MSS", 5: "nb_of_sim_conn", 6: "inactivity_time_out"},
    42: {2: "data_link_layer_reference", 3: "IP_address", 4: "multicast_IP_address",
         5: "IP_options", 6: "subnet_mask", 7: "gateway_IP_address", 8: "use_DHCP_flag",
         9: "primary_DNS_address", 10: "secondary_DNS_address"},
    43: {2: "MAC_address"},
    48: {2: "data_link_layer_reference", 3: "address_config_mode", 4: "unicast_IPv6_addresses",
         5: "multicast_IPv6_addresses", 6: "gateway_IPv6_addresses", 7: "primary_DNS_address",
         8: "secondary_DNS_address", 9: "traffic_class", 10: "neighbor_discovery_setup"},
    64: {2: "security_policy", 3: "security_suite", 4: "client_system_title",
         5: "server_system_title", 6: "certificates"},
}

# Candidats "info module / HPLC" (classe 1 = Data)
VENDOR_CANDIDATES = [
    "0.0.96.1.1.255", "0.0.96.1.2.255", "0.0.96.1.3.255", "0.0.96.1.4.255", "0.0.96.1.5.255",
    "0.0.96.1.6.255", "0.0.96.1.7.255", "0.0.96.1.8.255", "0.0.96.1.9.255",
    "0.0.0.2.1.255", "0.0.0.2.2.255", "0.0.0.2.3.255", "0.0.42.0.0.255",
    "0.0.96.12.0.255", "0.0.96.12.1.255", "0.0.96.12.2.255", "0.0.96.12.5.255",
    "0.0.128.0.0.255", "0.0.128.1.0.255", "0.0.128.2.0.255", "0.0.128.3.0.255",
    "0.1.128.0.0.255", "0.128.0.0.0.255",
]

KEYWORDS = ["G3", "HPLC", "HRF", "PLC", "CPL", "PRIME", "OFDM", "RF", "LORA", "NBIOT", "NB-IOT",
            "LTE", "GPRS", "WISUN", "WI-SUN", "ZIGBEE", "MODEM", "DCU", "CENELEC", "FCC", "ARIB",
            "6LOWPAN", "G.9903", "POWERLINE", "CARRIER"]

# Codes d'erreur DLMS Data-Access-Result
ERR_TXT = {1: "hardware-fault", 2: "temporary-failure", 3: "read-write-denied",
           4: "object-undefined", 9: "object-class-inconsistent", 11: "object-unavailable",
           12: "type-unmatched", 13: "scope-of-access-violated", 14: "data-block-unavailable",
           250: "other-reason"}


# ---------------------------------------------------------------------------
# Couche serie / HDLC
# ---------------------------------------------------------------------------
class Ctx:
    port = PORT
    client_id = CLIENT_ID
    password = PASSWORD.encode()
    auth = Authentication.LOW


def disc_bytes():
    tmp = GXDLMSClient(True, Ctx.client_id, 1, Authentication.NONE, None, InterfaceType.HDLC)
    f = tmp.disconnectRequest(True)
    return bytes(f) if f else b""


def reset_compteur():
    try:
        with serial.Serial(Ctx.port, 9600, timeout=0.5) as s:
            s.write(disc_bytes())
            s.read(100)
    except Exception:
        pass
    time.sleep(1)


def open_mode_e():
    s = serial.Serial(Ctx.port, 300, bytesize=7, parity="E", stopbits=1, timeout=3)
    s.reset_input_buffer()
    s.write(b"/?!\r\n")
    ident = s.read(64)
    if not ident.startswith(b"/"):
        s.close()
        raise ConnectionError(f"Pas de reponse optique. Recu: {ident}")
    raw_ident = ident.decode("ascii", errors="ignore").strip()
    s.write(b"\x06" + b"252" + b"\r\n")
    time.sleep(0.4)
    s.read(64)
    s.baudrate = 9600
    s.bytesize = 8
    s.parity = "N"
    s.stopbits = 1
    s.reset_input_buffer()
    time.sleep(0.2)
    return s, raw_ident


def read_hdlc(s, timeout=4.0):
    end = time.time() + timeout
    buf = b""
    while time.time() < end:
        chunk = s.read(s.in_waiting or 1)
        if chunk:
            buf += chunk
            i = buf.find(b"\x7e")
            if i >= 0 and len(buf) >= i + 3:
                buf = buf[i:]
                length = ((buf[1] & 0x07) << 8) | buf[2]
                if len(buf) >= length + 2:
                    return buf[:length + 2]
    raise TimeoutError("Timeout: pas de reponse HDLC.")


def transfer(s, client, frame, reply):
    s.write(bytes(frame))
    raw = read_hdlc(s)
    data = GXByteBuffer()
    data.set(raw)
    client.getData(data, reply)


def request(s, client, frames):
    if not frames:
        return None
    if not isinstance(frames, (list, tuple)):
        frames = [frames]
    reply = GXReplyData()
    for f in frames:
        reply.clear()
        data = f
        while True:
            transfer(s, client, data, reply)
            if reply.isMoreData():
                data = client.receiverReady(reply)
            else:
                break
    return reply


def connect():
    reset_compteur()
    s, raw_ident = open_mode_e()
    client = GXDLMSClient(True, Ctx.client_id, 1, Ctx.auth,
                          Ctx.password if Ctx.auth == Authentication.LOW else None,
                          InterfaceType.HDLC)
    r = request(s, client, client.snrmRequest())
    client.parseUAResponse(r.data)
    r = request(s, client, client.aarqRequest())
    client.parseAareResponse(r.data)
    return s, client, raw_ident


# ---------------------------------------------------------------------------
# Lecture avec classification du resultat
# ---------------------------------------------------------------------------
def classify(code, msg):
    m = (msg or "").lower()
    if code in (3, 13) or "denied" in m or "violat" in m:
        return "PROTEGE"          # l'objet existe, mais ce client n'y a pas droit
    if code == 9 or "inconsistent" in m:
        return "CLASSE_DIFF"      # l'objet existe avec une autre classe
    if code == 12 or "unmatched" in m:
        return "TYPE"             # l'objet existe, type d'attribut different
    if code == 4 or "undefined" in m:
        return "ABSENT"
    return "ERREUR"


def read_attr(s, client, obj, attr):
    """Retourne (statut, valeur_ou_message, code)."""
    try:
        reply = request(s, client, client.read(obj, attr))
        code = int(getattr(reply, "error", 0) or 0)
        if code:
            txt = ERR_TXT.get(code, "")
            return classify(code, txt), f"code {code} {txt}".strip(), code
        if reply.value is None:
            return "NUL", "valeur nulle sans code d'erreur", 0
        return "OK", reply.value, 0
    except TimeoutError:
        try:
            s.reset_input_buffer()
        except Exception:
            pass
        return "TIMEOUT", "timeout", 0
    except Exception as e:
        msg = str(e)
        code = None
        for name in ("errorCode", "error", "code"):
            c = getattr(e, name, None)
            if isinstance(c, int):
                code = int(c)
                break
        return classify(code, msg), msg[:120], code


def make_obj(class_id, ln):
    try:
        obj = GXDLMSClient.createObject(ObjectType(class_id))
        obj.logicalName = ln
        return obj
    except Exception:
        return None


# ---------------------------------------------------------------------------
# Formatage DECIMAL + annotations
# ---------------------------------------------------------------------------
def as_bytes(v):
    if isinstance(v, GXByteBuffer):
        return bytes(v.array())
    if isinstance(v, (bytes, bytearray)):
        return bytes(v)
    return None


def dec(v, depth=0):
    if v is None:
        return "null"
    b = as_bytes(v)
    if b is not None:
        return "[" + ", ".join(str(x) for x in b) + "]"
    if isinstance(v, bool):
        return "true" if v else "false"
    if isinstance(v, int):
        return str(int(v))
    if isinstance(v, float):
        return str(v)
    if isinstance(v, (list, tuple)):
        if depth >= 3 or len(v) > 30:
            return f"<{len(v)} elements>"
        return "[" + "; ".join(dec(x, depth + 1) for x in v) + "]"
    if hasattr(v, "value") and isinstance(getattr(v, "value"), int):
        return str(int(v.value))
    return str(v)


def text_of(v):
    b = as_bytes(v)
    if b is not None:
        return "".join(chr(x) if 32 <= x < 127 else " " for x in b).strip()
    if isinstance(v, (list, tuple)):
        return " ".join(text_of(x) for x in v)
    if isinstance(v, (int, float, bool)) or v is None:
        return ""
    return str(v)


def collect_16_bytes(v, acc):
    b = as_bytes(v)
    if b is not None:
        if len(b) == 16:
            acc.append(b)
        return
    if isinstance(v, (list, tuple)):
        for x in v:
            collect_16_bytes(x, acc)


def pretty(cls, attr, val):
    """Annotation lisible (IPv4, IPv6, MAC/EUI-64, hex) pour certains attributs."""
    try:
        if cls == 42 and attr in (3, 4, 6, 7, 9, 10) and isinstance(val, int):
            return ".".join(str((int(val) >> s) & 255) for s in (24, 16, 8, 0))
        if cls == 48 and attr in (4, 5, 6, 7, 8):
            acc = []
            collect_16_bytes(val, acc)
            return " | ".join(str(ipaddress.IPv6Address(x)) for x in acc)
        b = as_bytes(val)
        if cls == 43 and attr == 2 and b is not None:
            return ":".join(f"{x:02X}" for x in b) + f"  ({len(b)} octets)"
        if cls == 64 and attr in (4, 5) and b is not None:
            return ":".join(f"{x:02X}" for x in b) + f"  ascii='{text_of(b)}'"
        if cls == 91 and attr in (2, 4) and isinstance(val, int):
            return f"0x{int(val):04X}"
    except Exception:
        pass
    return ""


def to_int(v):
    if isinstance(v, bool):
        return int(v)
    if isinstance(v, int):
        return int(v)
    if hasattr(v, "value") and isinstance(getattr(v, "value"), int):
        return int(v.value)
    return None


def find_keywords(text):
    up = text.upper()
    found = []
    for kw in KEYWORDS:
        if len(kw) <= 4:
            if re.search(r"(?<![A-Z0-9])" + re.escape(kw) + r"(?![A-Z0-9])", up):
                found.append(kw)
        elif kw in up:
            found.append(kw)
    return found


def obis_parts(ln):
    try:
        return [int(x) for x in ln.split(".")]
    except Exception:
        return None


STATUS_LABEL = {
    "OK": "LISIBLE", "PROTEGE": "PRESENT mais ACCES REFUSE", "CLASSE_DIFF": "PRESENT (autre classe)",
    "TYPE": "PRESENT (type different)", "ABSENT": "absent", "NUL": "reponse vide",
    "TIMEOUT": "timeout", "ERREUR": "erreur",
}


# ---------------------------------------------------------------------------
# Sondage d'un objet (tous les attributs)
# ---------------------------------------------------------------------------
def probe_object(s, client, family, desc, cls, ln, max_attr, out):
    entry = {"family": family, "desc": desc, "class": cls, "obis": ln,
             "status": None, "message": "", "attrs": {}}
    obj = make_obj(cls, ln)
    if obj is None:
        # Classe inconnue de la bibliotheque : test d'EXISTENCE seulement, en lisant l'OBIS
        # comme un objet Data (classe 1). "classe differente" ou "acces refuse" = l'objet existe.
        st, val, code = read_attr(s, client, GXDLMSData(ln), 2)
        entry["status"] = st if st != "OK" else "CLASSE_DIFF"
        entry["message"] = f"test d'existence via classe 1 : {val if st != 'OK' else 'lisible'}"
        entry["existence_only"] = True
        out(f"[~] {family:9s} c{cls:<3d} {ln:16s} {desc[:34]:34s} -> "
            f"{STATUS_LABEL.get(entry['status'], entry['status'])} (existence seulement : {entry['message'][:70]})")
        return entry

    # Nombre reel d'attributs fourni par la bibliotheque (sinon valeur par defaut)
    try:
        if type(obj).__name__ != "GXDLMSObject":
            n_attr = int(obj.getAttributeCount())
            if n_attr > 1:
                max_attr = n_attr
    except Exception:
        pass

    found, fails = False, 0
    for attr in range(2, max_attr + 1):
        st, val, code = read_attr(s, client, obj, attr)
        name = ATTR_NAMES.get(cls, {}).get(attr, f"attr{attr}")
        if st == "OK":
            found, fails = True, 0
            entry["status"] = "OK"
            note = pretty(cls, attr, val)
            entry["attrs"][attr] = {"name": name, "decimal": dec(val), "note": note,
                                    "text": text_of(val), "raw": val}
            extra = f"   <= {note}" if note else ""
            out(f"[+] {family:9s} c{cls:<3d} {ln} a{attr:02d} {name} = {dec(val)}{extra}")
        else:
            if not found:
                entry["status"] = st
                entry["message"] = str(val)
                if st in ("ABSENT", "PROTEGE", "CLASSE_DIFF", "TYPE", "ERREUR", "NUL", "TIMEOUT"):
                    break      # inutile d'insister sur les autres attributs
            else:
                fails += 1
                if fails >= 3:
                    break
    if entry["status"] != "OK":
        out(f"[-] {family:9s} c{cls:<3d} {ln:16s} {desc[:34]:34s} -> "
            f"{STATUS_LABEL.get(entry['status'], entry['status'])} ({entry['message']})")
    return entry


# ---------------------------------------------------------------------------
# Test complet pour un client
# ---------------------------------------------------------------------------
def run_client(client_id, auth_name, report, out):
    Ctx.client_id = client_id
    Ctx.auth = Authentication.LOW if auth_name == "low" else Authentication.NONE
    res = {"client": client_id, "auth": auth_name, "connected": False, "probes": [],
           "sweep": [], "identity": {}, "vendor": [], "keywords": [], "view_classes": {}}

    out("")
    out("#" * 72)
    out(f"#  CLIENT DLMS {client_id}  (authentification : {auth_name.upper()})")
    out("#" * 72)
    try:
        s, client, raw_ident = connect()
    except Exception as e:
        out(f"[-] Connexion / authentification impossible avec ce client : {e}")
        res["error"] = str(e)
        return res
    res["connected"] = True
    res["optical_ident"] = raw_ident
    out(f"[+] Association reussie. Identification optique : {raw_ident}")

    # ---- A. Association View
    out("\n--- A. ASSOCIATION VIEW ---")
    view = []
    try:
        r = request(s, client, client.getObjectsRequest())
        client.parseObjects(r.data, True)
        for o in client.objects:
            cls = int(o.objectType.value) if hasattr(o.objectType, "value") else int(o.objectType)
            view.append((cls, o.logicalName))
    except Exception as e:
        out(f"[!] Association View illisible : {e}")
    counts = {}
    for cls, _ in view:
        counts[cls] = counts.get(cls, 0) + 1
    res["view_classes"] = counts
    out(f"[*] {len(view)} objets. Classes : " + ", ".join(f"{c}:{n}" for c, n in sorted(counts.items())))
    in_view = [(c, ln) for c, ln in view if c in FAMILY_BY_CLASS]
    if in_view:
        out("[+] Objets CPL/RF/IP DECLARES dans la vue :")
        for c, ln in sorted(in_view):
            out(f"      {FAMILY_BY_CLASS[c]:9s} classe {c:3d} {ln}")
    else:
        out("[-] Aucun objet CPL/RF/IP declare dans la vue de ce client.")

    # ---- B + C. Sondage (OBIS de la vue en priorite, sinon OBIS standard)
    out("\n--- B/C. LECTURE DES OBJETS G3-PLC / RF / IP / PRIME / S-FSK (valeurs en decimal) ---")
    view_ln = {}
    for c, ln in in_view:
        view_ln.setdefault(c, ln)
    probed_ln = set()
    for fam, desc, cls, ln, n in PROBES:
        real_ln = view_ln.get(cls, ln)
        e = probe_object(s, client, fam, desc, cls, real_ln, n, out)
        if not e.get("existence_only"):
            probed_ln.add(real_ln)   # les autres sont deja testes via classe 1, inutile de les refaire
        e["in_view"] = cls in view_ln
        res["probes"].append(e)

    # ---- D. Balayage OBIS 0.b.25..29
    out("\n--- D. BALAYAGE OBIS DE COMMUNICATION (0.0/0.1 . 25..29 . 0..15 . 0 . 255) ---")
    hits, stats = [], {}
    for b in (0, 1):
        for c in range(25, 30):
            for x in range(0, 16):
                ln = f"0.{b}.{c}.{x}.0.255"
                if ln in probed_ln:
                    continue
                st, val, code = read_attr(s, client, GXDLMSData(ln), 2)
                stats[st] = stats.get(st, 0) + 1
                if st in ("OK", "PROTEGE", "CLASSE_DIFF", "TYPE"):
                    hits.append({"obis": ln, "status": st, "message": str(val) if st != "OK" else dec(val)})
                    out(f"[+] {ln:16s} {STATUS_LABEL[st]}  ({hits[-1]['message'][:80]})")
    res["sweep"] = hits
    out(f"[*] Balayage termine : {len(hits)} objet(s) existant(s). Statuts : {stats}")

    # ---- E. Identite
    out("\n--- E. IDENTITE (EUI-64 / MAC / IPv4 / IPv6) ---")
    ident = res["identity"]
    for e in res["probes"]:
        if e["status"] != "OK":
            continue
        a = e["attrs"]
        if e["class"] == 42 and 3 in a:
            ident["IPv4"] = a[3]["note"] or a[3]["decimal"]
        if e["class"] == 48 and 4 in a:
            ident["IPv6"] = a[4]["note"] or a[4]["decimal"]
        if e["class"] == 43 and 2 in a:
            ident["MAC/EUI-64"] = a[2]["note"] or a[2]["decimal"]
        if e["class"] == 91:
            if 2 in a:
                ident["short_address"] = to_int(a[2]["raw"])
            if 4 in a:
                ident["PAN_id"] = to_int(a[4]["raw"])
    # system title (classe 64) : present dans la vue du client 18
    for c, ln in view:
        if c == 64:
            obj = make_obj(64, ln)
            for attr in (4, 5):
                st, val, code = read_attr(s, client, obj, attr)
                if st == "OK":
                    ident[f"{ln} {ATTR_NAMES[64][attr]}"] = pretty(64, attr, val) or dec(val)
    sh, pan = ident.get("short_address"), ident.get("PAN_id")
    if isinstance(sh, int) and isinstance(pan, int) and sh != UNASSIGNED and pan != UNASSIGNED:
        iid_pan = pan & ~0x0200  # bit U/L a 0 (RFC 4944)
        ident["IPv6 link-local attendue (RFC 4944, indicatif)"] = (
            f"fe80::{iid_pan:x}:ff:fe00:{sh:x}")
    if ident:
        for k, v in ident.items():
            out(f"   {k:48s}: {v}")
        out("   -> Compare l'EUI-64 / system title avec la liste des noeuds du DCU.")
    else:
        out("   Aucune identite lisible (classes 41-48/90-92 non accessibles a ce client).")

    # ---- F. HPLC / proprietaire
    out("\n--- F. HPLC : OBJETS PROPRIETAIRES & MOTS-CLES (pas de classe COSEM HPLC standard) ---")
    vendor = []
    for c, ln in view:
        p = obis_parts(ln)
        if p and len(p) == 6 and (p[1] >= 128 or p[2] >= 128 or c >= 128):
            vendor.append((c, ln))
    cand = [(1, ln) for ln in VENDOR_CANDIDATES if all(ln != v[1] for v in vendor)]
    kw_hits = []
    for c, ln in vendor + cand:
        obj = make_obj(c, ln) or GXDLMSData(ln)
        for attr in ((2, 3) if c == 3 else (2,)):
            st, val, code = read_attr(s, client, obj, attr)
            if st == "OK":
                t = text_of(val)
                res["vendor"].append({"obis": ln, "class": c, "attr": attr, "decimal": dec(val)})
                tag = " [proprietaire]" if (c, ln) in vendor else ""
                out(f"[+] {ln:16s} classe {c:3d} a{attr} = {dec(val)[:110]}{tag}")
                kws = find_keywords(t)
                if kws:
                    kw_hits.append({"obis": ln, "keywords": kws, "text": t[:80]})
                    out(f"    !! mots-cles {kws} dans '{t[:80]}'")
    res["keywords"] = kw_hits
    if not res["vendor"]:
        out("[-] Aucun objet proprietaire / info-module lisible avec ce client.")

    # ---- G. Verdict client
    out("\n--- G. VERDICT POUR CE CLIENT ---")
    res["verdict"] = verdict(res, out)

    try:
        s.write(disc_bytes())
        s.close()
    except Exception:
        pass
    return res


def verdict(res, out):
    v = {}
    probes = res["probes"]

    def level(fam):
        es = [e for e in probes if e["family"] == fam]
        if any(e["status"] == "OK" for e in es):
            return "CONFIRME (objet lisible)"
        if any(e["status"] in ("PROTEGE", "CLASSE_DIFF", "TYPE") for e in es):
            return "PRESENT mais non accessible a ce client"
        return "non trouve"

    for fam in ("G3-PLC", "G3-PLC-RF", "Wi-SUN", "IP", "PRIME", "S-FSK"):
        v[fam] = level(fam)
    comm_sweep = [h for h in res["sweep"] if h["obis"].split(".")[2] in ("26", "27", "28", "29")]
    if comm_sweep and v["G3-PLC"] == "non trouve":
        v["G3-PLC"] = "INDICE : des OBIS 0.x.26-29 existent (voir D)"

    # Etat reseau G3
    state = None
    g3 = {e["class"]: e for e in probes if e["family"] == "G3-PLC" and e["status"] == "OK"}
    if g3:
        short = to_int(g3[91]["attrs"][2]["raw"]) if 91 in g3 and 2 in g3[91]["attrs"] else None
        pan = to_int(g3[91]["attrs"][4]["raw"]) if 91 in g3 and 4 in g3[91]["attrs"] else None
        cnt = {}
        if 90 in g3:
            for a, nme in ATTR_NAMES[90].items():
                if a in g3[90]["attrs"]:
                    cnt[nme] = to_int(g3[90]["attrs"][a]["raw"]) or 0
        tx = sum(x for k, x in cnt.items() if k.startswith("tx"))
        rx = sum(x for k, x in cnt.items() if k.startswith("rx"))
        joined = (short is not None and pan is not None and short != UNASSIGNED
                  and pan not in (0, UNASSIGNED))
        if joined:
            state = f"RESEAU REJOINT (PAN {pan}, adresse courte {short}), TX={tx}, RX={rx}"
        elif short is not None or pan is not None:
            state = f"G3-PLC present mais RESEAU NON REJOINT (PAN {pan}, adresse courte {short}), TX={tx}, RX={rx}"
        else:
            state = f"G3-PLC lisible, etat reseau indetermine, TX={tx}, RX={rx}"
        v["etat_G3"] = state

    hplc_idx = len(res["vendor"]) + len(res["keywords"])
    v["HPLC"] = ("aucun indice dans les objets lus" if hplc_idx == 0 else
                 f"{len(res['vendor'])} objet(s) proprietaire(s) lu(s), "
                 f"{len(res['keywords'])} mot(s)-cle(s) : a valider avec la fiche fabricant")

    for k, val in v.items():
        out(f"   {k:10s}: {val}")
    return v


# ---------------------------------------------------------------------------
# Programme principal
# ---------------------------------------------------------------------------
def parse_clients(spec, default_client, default_auth):
    out_list = []
    for part in spec.split(","):
        part = part.strip()
        if not part:
            continue
        if ":" in part:
            c, a = part.split(":", 1)
            out_list.append((int(c), a.strip().lower()))
        else:
            out_list.append((int(part), default_auth))
    return out_list or [(default_client, default_auth)]


def main():
    ap = argparse.ArgumentParser(description="Test approfondi G3-PLC / RF / HPLC / IP")
    ap.add_argument("--port", default=PORT)
    ap.add_argument("--client", type=int, default=CLIENT_ID)
    ap.add_argument("--clients", default="", help='ex: "18:low,16:none,17:low,32:low"')
    ap.add_argument("--password", default=PASSWORD)
    ap.add_argument("--auth", choices=["none", "low"], default="low")
    ap.add_argument("--out", default="test_g3plc_rf")
    args = ap.parse_args()

    Ctx.port = args.port
    Ctx.password = args.password.encode()
    clients = parse_clients(args.clients, args.client, args.auth) if args.clients \
        else [(args.client, args.auth)]
    out_base = f"{args.out}_{datetime.now().strftime('%Y%m%d_%H%M%S')}"

    report = []

    def out(line=""):
        print(line)
        report.append(line)

    results = []
    for cid, an in clients:
        try:
            results.append(run_client(cid, an, report, out))
        except KeyboardInterrupt:
            out("\n[!] Interrompu par l'utilisateur.")
            break
        except Exception as e:
            out(f"[!] Erreur inattendue avec le client {cid} : {e}")

    # ---- Synthese globale
    out("\n" + "=" * 72)
    out("  SYNTHESE GLOBALE")
    out("=" * 72)
    for r in results:
        if not r["connected"]:
            out(f"Client {r['client']:3d} ({r['auth']:4s}) : connexion refusee")
            continue
        v = r.get("verdict", {})
        out(f"Client {r['client']:3d} ({r['auth']:4s}) : G3-PLC = {v.get('G3-PLC')} | "
            f"RF hybride = {v.get('G3-PLC-RF')} | Wi-SUN = {v.get('Wi-SUN')} | IP = {v.get('IP')}")
        if v.get("etat_G3"):
            out(f"      etat G3 : {v['etat_G3']}")
    best = [r for r in results if r["connected"]
            and "CONFIRME" in str(r.get("verdict", {}).get("G3-PLC"))]
    out("")
    if best:
        out("CONCLUSION : G3-PLC CONFIRME par lecture directe des objets COSEM (classes 90/91/92).")
        out("  Preuve complementaire : adresse IPv6 / EUI-64 / PAN ID ci-dessus (a comparer au DCU).")
    else:
        out("CONCLUSION : aucune lecture directe G3-PLC reussie avec les clients testes.")
        out("  - 'PRESENT mais ACCES REFUSE' = l'objet existe : il faut un client / mot de passe")
        out("    plus privilegie (voir la table des associations, classe 15).")
        out("  - 'absent' partout = ces objets ne sont pas exposes en DLMS par ce firmware ;")
        out("    la confirmation passe alors par le DCU (liste des noeuds, EUI-64, PAN ID)")
        out("    ou par la documentation Inhemeter de ce modele.")
    out("RAPPEL : une adresse IPv4 ne prouve pas le G3-PLC (G3 = IPv6 + EUI-64).")
    out("         L'HPLC n'a pas de classe COSEM standard : seul le fabricant peut le confirmer.")

    txt_name = f"{out_base}.txt"
    with open(txt_name, "w", encoding="utf-8") as f:
        f.write("\n".join(report))

    def clean(o):
        if isinstance(o, dict):
            return {k: clean(v) for k, v in o.items() if k != "raw"}
        if isinstance(o, list):
            return [clean(x) for x in o]
        return o

    json_name = f"{out_base}.json"
    with open(json_name, "w", encoding="utf-8") as f:
        json.dump({"date": datetime.now().isoformat(), "port": Ctx.port, "results": clean(results)},
                  f, ensure_ascii=False, indent=2, default=str)
    print(f"\n[*] Rapport texte : {txt_name}")
    print(f"[*] Rapport JSON  : {json_name}")


if __name__ == "__main__":
    main()
