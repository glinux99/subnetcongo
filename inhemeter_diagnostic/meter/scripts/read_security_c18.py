import sys
sys.path.insert(0, r"C:\Users\Daniel\AppData\Local\Temp\opencode")
import test_g3plc_rf_approfondi as T
from gurux_dlms.enums import Authentication

T.Ctx.port = "COM5"
T.Ctx.password = b"12345678"
T.Ctx.client_id = 18
T.Ctx.auth = Authentication.LOW
s, c, ident = T.connect()
print("ident", ident)
r = T.request(s, c, c.getObjectsRequest())
c.parseObjects(r.data, True)
for o in c.objects:
    ct = int(o.objectType.value) if hasattr(o.objectType, "value") else int(o.objectType)
    if ct == 64:
        print("SEC", o.logicalName)
        for a in range(1, 8):
            st, v, code = T.read_attr(s, c, o, a)
            if st == "OK":
                print(f"  a{a} OK {T.dec(v)[:260]}")
                t = T.text_of(v)
                if t and t != T.dec(v):
                    print(f"      text={t[:120]!r}")
            else:
                print(f"  a{a} {st} {v} code={code}")
try:
    s.write(T.disc_bytes()); s.close()
except Exception:
    pass
