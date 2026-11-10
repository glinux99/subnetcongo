import serial, time

port = "COM4"

def probe(baud, bytesize, parity, stopbits, label, dtr=None, rts=None):
    try:
        s = serial.Serial(port, baud, bytesize=bytesize, parity=parity, stopbits=stopbits, timeout=1.0, write_timeout=1)
        if dtr is not None:
            s.dtr = dtr
        if rts is not None:
            s.rts = rts
        time.sleep(0.25)
        s.reset_input_buffer(); s.reset_output_buffer()
        s.write(b"/?!\r\n")
        time.sleep(0.7)
        d = s.read(256)
        print(f"OPEN {label} dtr={s.dtr} rts={s.rts} cts={s.cts} dsr={s.dsr} cd={s.cd} rx={d!r}", flush=True)
        s.close()
    except Exception as e:
        print(f"ERR  {label} -> {type(e).__name__}: {e}", flush=True)

for dtr,rts in [(False,False),(True,True),(True,False),(False,True)]:
    probe(300,7,'E',1,f"300_7E1_dtr{int(dtr)}_rts{int(rts)}",dtr=dtr,rts=rts)
for baud,bs,par,st in [(300,7,'E',1),(300,7,'N',1),(300,8,'N',1),(9600,8,'N',1),(9600,7,'E',1),(1200,7,'E',1),(2400,7,'E',1)]:
    probe(baud,bs,par,st,f"{baud}_{bs}{par}{st}")
try:
    s = serial.Serial(port, 9600, 8, 'N', 1, timeout=0.8)
    s.dtr=True; s.rts=True
    time.sleep(0.2); s.reset_input_buffer(); time.sleep(1.0)
    d=s.read(256)
    print("LISTEN 9600_8N1_dtr1_rts1 rx=%r" % (d,), flush=True)
    s.close()
except Exception as e:
    print("LISTEN err", e, flush=True)
