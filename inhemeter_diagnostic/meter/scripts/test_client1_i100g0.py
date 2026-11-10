import sys, time
sys.path.insert(0, r"C:\Users\Daniel\AppData\Local\Temp\opencode")
import test_g3plc_rf_approfondi as T
from gurux_dlms.enums import Authentication

T.Ctx.port = "COM5"
T.Ctx.client_id = 1

AUTHS = [
    ("none", Authentication.NONE),
    ("low", Authentication.LOW),
    ("high", Authentication.HIGH),
    ("high_gmac", Authentication.HIGH_GMAC),
    ("high_md5", Authentication.HIGH_MD5),
    ("high_sha1", Authentication.HIGH_SHA1),
    ("high_sha256", Authentication.HIGH_SHA256),
]
PASSWORDS = [
    b"12345678",
    b"00000000",
    b"11111111",
    b"22222222",
    b"33333333",
    b"44444444",
    b"55555555",
    b"66666666",
    b"77777777",
    b"88888888",
    b"99999999",
    b"1234567",
    b"abcdefgh",
    b"ABCDEFGH",
    b"inhemeter",
    b"INHEMETER",
    b"Inhemeter",
    b"0000000000000000",
    bytes.fromhex("30303030303030303030303030303030"),
    bytes.fromhex("3132333435363738"),
]

def attempt(aname, auth, pw):
    T.Ctx.password = pw
    T.Ctx.auth = auth
    try:
        s, c, ident = T.connect()
        r = T.request(s, c, c.getObjectsRequest())
        c.parseObjects(r.data, True)
        sec = [o.logicalName for o in c.objects if (int(o.objectType.value) if hasattr(o.objectType, "value") else int(o.objectType)) == 64]
        comm_classes = {41,42,43,47,48,64,90,91,92,95,96,97,98,160,161,162}
        comm = [(int(o.objectType.value) if hasattr(o.objectType,"value") else int(o.objectType), o.logicalName) for o in c.objects if (int(o.objectType.value) if hasattr(o.objectType,"value") else int(o.objectType)) in comm_classes]
        print(f"OK  client=1 auth={aname} pw={pw!r} ident={ident} objects={len(c.objects)} sec={sec} comm={comm}", flush=True)
        for o in c.objects:
            if (int(o.objectType.value) if hasattr(o.objectType, "value") else int(o.objectType)) == 64:
                for a in (2,3,4,5):
                    st,v,code = T.read_attr(s,c,o,a)
                    print(f"    sec {o.logicalName} a{a}: {st} {T.dec(v)[:180] if st=='OK' else v}", flush=True)
        try:
            s.write(T.disc_bytes()); s.close()
        except Exception:
            pass
        return True
    except Exception as e:
        msg = str(e).replace("\r"," ").replace("\n"," ")[:160]
        print(f"FAIL client=1 auth={aname} pw={pw!r} -> {msg}", flush=True)
        time.sleep(0.6)
        return False

print("=== i100 G0 / client 1 DLMS auth scan (COM5) ===", flush=True)
print(f"port={T.Ctx.port} time={time.strftime('%Y-%m-%d %H:%M:%S')}", flush=True)
for aname, auth in AUTHS:
    pws = [b""] if aname == "none" else PASSWORDS
    for pw in pws:
        if attempt(aname, auth, pw):
            print("MATCH_FOUND", flush=True)
            sys.exit(0)
print("NO_MATCH", flush=True)
