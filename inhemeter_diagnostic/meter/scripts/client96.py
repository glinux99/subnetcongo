import sys
sys.path.insert(0, r"C:\Users\Daniel\AppData\Local\Temp\opencode")
import test_g3plc_rf_approfondi as T

_orig_read = T.read_hdlc
T.read_hdlc = lambda s, timeout=2.5: _orig_read(s, timeout=timeout)

T.Ctx.port = "COM5"; T.Ctx.password = b"12345678"
T.Ctx.client_id = 96; T.Ctx.auth = T.Authentication.LOW
s, client, ident = T.connect()
r = T.request(s, client, client.getObjectsRequest())
client.parseObjects(r.data, True)
view = [(int(o.objectType.value) if hasattr(o.objectType,"value") else int(o.objectType), o.logicalName) for o in client.objects]
print("CLIENT 96 VIEW:", len(view))
for c, ln in sorted(view):
    print(f"  c{c:3d} {ln}")
print("\nREAD ALL:")
for c, ln in sorted(view):
    o = T.make_obj(c, ln)
    if o is None:
        print(f"  c{c:3d} {ln} (classe non supportee)"); continue
    for a in range(2, 12):
        st, val, code = T.read_attr(s, client, o, a)
        if st == "OK":
            t = T.text_of(val)
            print(f"  c{c:3d} {ln} a{a} = {T.dec(val)[:220]}   | '{t[:90]}'")
        else:
            if a == 2:
                print(f"  c{c:3d} {ln} a{a} -> {st} {val}")
            break
try:
    s.write(T.disc_bytes()); s.close()
except Exception:
    pass
