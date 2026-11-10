import sys, re
sys.path.insert(0, r"C:\Users\Daniel\AppData\Local\Temp\opencode")
import test_g3plc_rf_approfondi as T

_orig_read = T.read_hdlc
def _fast_read(s, timeout=2.5):
    return _orig_read(s, timeout=timeout)
T.read_hdlc = _fast_read

T.Ctx.port = "COM5"
T.Ctx.password = b"12345678"

COMM_CLASSES = {41,42,43,47,48,50,51,52,53,55,56,80,81,82,83,84,85,86,90,91,92,95,96,97,98,160,161,162}
CLIENTS = [(16,"none"),(96,"none"),(97,"none"),(102,"none"),
           (16,"low"),(96,"low"),(97,"low"),(102,"low")]

for cid, an in CLIENTS:
    T.Ctx.client_id = cid
    T.Ctx.auth = T.Authentication.LOW if an == "low" else T.Authentication.NONE
    print("\n" + "="*70, flush=True)
    print(f"CLIENT {cid} ({an})", flush=True)
    try:
        s, client, ident = T.connect()
    except Exception as e:
        print("  ECHEC:", e, flush=True)
        continue
    print("  OK ident:", ident, flush=True)
    try:
        r = T.request(s, client, client.getObjectsRequest())
        client.parseObjects(r.data, True)
        view = [(int(o.objectType.value) if hasattr(o.objectType,"value") else int(o.objectType), o.logicalName) for o in client.objects]
        counts = {}
        for c,_ in view:
            counts[c] = counts.get(c,0)+1
        print(f"  {len(view)} objets, classes: {dict(sorted(counts.items()))}", flush=True)
        comm = [(c,ln) for c,ln in view if c in COMM_CLASSES]
        for c,ln in sorted(comm):
            o = T.make_obj(c,ln)
            print(f"    COMM c{c} {ln}", flush=True)
            for a in range(2, 8):
                st,val,code = T.read_attr(s, client, o, a)
                print(f"       a{a} -> {st} {T.dec(val)[:200] if st=='OK' else val}", flush=True)
                if st != "OK":
                    break
        # GSM diagnostic class 47
        o = T.make_obj(47, "0.0.42.0.0.255")
        if o:
            print("    GSM diag 0.0.42.0.0.255:", flush=True)
            for a in range(2, 9):
                st,val,code = T.read_attr(s, client, o, a)
                print(f"       a{a} -> {st} {T.dec(val)[:200] if st=='OK' else val}", flush=True)
    except Exception as e:
        print("  ERREUR apres connexion:", e, flush=True)
    try:
        s.write(T.disc_bytes()); s.close()
    except Exception:
        pass
