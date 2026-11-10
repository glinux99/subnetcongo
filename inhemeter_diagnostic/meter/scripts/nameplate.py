import sys
sys.path.insert(0, r"C:\Users\Daniel\AppData\Local\Temp\opencode")
import test_g3plc_rf_approfondi as T

_orig = T.read_hdlc
T.read_hdlc = lambda s, timeout=3.0: _orig(s, timeout=timeout)

T.Ctx.port = "COM5"; T.Ctx.password = b"12345678"
T.Ctx.client_id = 18; T.Ctx.auth = T.Authentication.LOW
s, client, ident = T.connect()
r = T.request(s, client, client.getObjectsRequest())
client.parseObjects(r.data, True)
view = {(int(o.objectType.value) if hasattr(o.objectType,"value") else int(o.objectType)): [] for o in client.objects}
for o in client.objects:
    c = int(o.objectType.value) if hasattr(o.objectType,"value") else int(o.objectType)
    view.setdefault(c, []).append(o.logicalName)

targets = [
    (1, "0.0.94.91.9.255"),  (1, "0.0.94.91.11.255"), (1, "0.0.96.1.1.255"),
    (1, "0.0.96.1.2.255"),   (1, "0.0.96.1.4.255"),   (1, "0.0.96.74.6.255"),
    (7, "0.0.94.91.10.255"), (7, "1.0.94.91.10.255"),
]
for c, ln in targets:
    o = T.make_obj(c, ln)
    if o is None:
        print(c, ln, "no class"); continue
    print(f"\n--- c{c} {ln} ---")
    for a in range(2, 8):
        st, val, code = T.read_attr(s, client, o, a)
        print(f"  a{a} -> {st} {T.dec(val)[:600] if st=='OK' else val}")
        if st != "OK":
            break

# Nameplate profile: read buffer attrs 2 (value) and 7 (entries)
o = T.make_obj(7, "0.0.94.91.10.255")
if o:
    print("\n--- Nameplate profile entries ---")
    st, val, code = T.read_attr(s, client, o, 7)
    print("a7 entries:", st, T.dec(val)[:1500] if st == "OK" else val)
    st, val, code = T.read_attr(s, client, o, 2)
    print("a2 buffer:", st, T.dec(val)[:1500] if st == "OK" else val)
try:
    s.write(T.disc_bytes()); s.close()
except Exception:
    pass
