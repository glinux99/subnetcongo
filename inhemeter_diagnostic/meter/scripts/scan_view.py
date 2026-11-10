import sys, re, json
sys.path.insert(0, r"C:\Users\Daniel\AppData\Local\Temp\opencode")
import test_g3plc_rf_approfondi as T

_orig_read = T.read_hdlc
def _fast_read(s, timeout=2.0):
    return _orig_read(s, timeout=timeout)
T.read_hdlc = _fast_read

T.Ctx.port = "COM5"
T.Ctx.client_id = 18
T.Ctx.password = b"12345678"
T.Ctx.auth = T.Authentication.LOW

s, client, ident = T.connect()
print("IDENT:", ident)

r = T.request(s, client, client.getObjectsRequest())
client.parseObjects(r.data, True)
view = [(int(o.objectType.value) if hasattr(o.objectType, "value") else int(o.objectType), o.logicalName) for o in client.objects]
print("TOTAL OBJECTS:", len(view))

print("\n=== CLASS 15 (ASSOCIATION LN) ===")
for c, ln in view:
    if c == 15:
        o = T.make_obj(15, ln)
        for a in (1,2,3,4,5,6):
            st, val, code = T.read_attr(s, client, o, a)
            print(f"  {ln} a{a} -> {st} {T.dec(val)[:300] if st=='OK' else val}")

print("\n=== CLASS 1 OBJECTS IN VIEW (text + keywords) ===", flush=True)
KW = ["G3","HPLC","HRF","PLC","CPL","PRIME","OFDM","RF","LORA","NBIOT","LTE","GPRS","WISUN","ZIGBEE","MODEM","DCU","CENELEC","FCC","6LOWPAN","POWERLINE","CARRIER","INHE","MODULE","COMM"]
found = []
for c, ln in view:
    if c != 1:
        continue
    o = T.make_obj(1, ln)
    st, val, code = T.read_attr(s, client, o, 2)
    print(f"  . {ln:16s} {st}", flush=True)
    if st == "OK":
        t = T.text_of(val)
        kws = T.find_keywords(t)
        printable = "".join(ch for ch in t if 32 <= ord(ch) < 127)
        if printable.strip():
            found.append((ln, T.dec(val)[:160], printable[:80], kws))
for ln, d, t, kws in found:
    mark = " <<<" if kws else ""
    print(f"  {ln:16s} = {d:40s} | '{t}' {kws}{mark}")

print("\nTOTAL textual class1:", len(found))
try:
    s.write(T.disc_bytes()); s.close()
except Exception:
    pass
