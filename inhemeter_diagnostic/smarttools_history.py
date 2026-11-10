import sqlite3
db=r'C:\Users\Daniel\AppData\Local\Programs\SmartTools3\DevModel\history.db'
con=sqlite3.connect('file:'+db+'?mode=ro',uri=True); cur=con.cursor()
cur.execute("select name from sqlite_master where type='table'")
for (t,) in cur.fetchall():
    cur.execute(f'pragma table_info("{t}")'); cols=[r[1] for r in cur.fetchall()]
    print('\nT',t,cols)
    q=None
    for c in cols:
        if any(k in c.lower() for k in ['xml','param','value','data','content','frame','apdu','cmd','desc']):
            q=c; break
    if q:
        try:
            cur.execute(f'select * from "{t}" order by rowid desc limit 8')
            for row in cur.fetchall():
                d=dict(zip(cols,row))
                s=str(d.get(q,''))[:1200].replace('\r',' ').replace('\n',' ')
                head=' | '.join(f"{k}={str(d[k])[:100]}" for k in cols[:8])
                hit=any(k in (head+s).lower() for k in ['037990133045','037990133102','gmac','hls','cipher','systemtitle','0.0.43.0.0.255','0-0:43.0.0.255','security','authentication','class 64'])
                if hit or t.lower().find('hist')>=0:
                    print('  >',head)
                    if s.strip(): print('    ',s[:700])
        except Exception as e: print(' err',e)
con.close()
