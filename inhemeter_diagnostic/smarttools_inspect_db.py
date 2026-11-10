import sqlite3, os
base=r'C:\Users\Daniel\AppData\Local\Programs\SmartTools3\DevModel'
targets=['database.db','history.db','authority.db','ProtocolDB.db']
keywords=['037990133045','037990133102','260600010','192.168.202.159','i100','dm_i100','gmac','hls','cipher','ek','ak','systitle','systemtitle','auth','password','12345678','00000000','submodel','gateway','clientid','security']
for name in targets:
    p=os.path.join(base,name)
    if not os.path.exists(p):
        print('\n###',name,'missing'); continue
    print('\n###',name, os.path.getsize(p))
    con=sqlite3.connect('file:'+p+'?mode=ro', uri=True)
    cur=con.cursor()
    cur.execute("select name,sql from sqlite_master where type='table' order by name")
    for t,sql in cur.fetchall():
        try:
            cur.execute(f'select * from "{t}" limit 0'); cols=[d[0] for d in cur.description]
        except Exception:
            continue
        hay=(t+' '+' '.join(cols)+' '+(sql or '')).lower()
        if any(k in hay for k in ['meter','device','sec','key','auth','client','dlms','comm','conn','param','model','cred','pass','hls','gmac','cipher','gateway','node','topo','sub']):
            print(' T',t,'cols=',cols[:30])
            try:
                cur.execute(f'select * from "{t}" limit 30')
                rows=cur.fetchall()
                shown=0
                for row in rows:
                    s=' | '.join(('' if v is None else str(v))[:140].replace('\r',' ').replace('\n',' ') for v in row)
                    sl=s.lower()
                    if any(k in sl for k in keywords) or shown < 3:
                        print('  >',s[:900]); shown+=1
                    if shown>=12: break
            except Exception as e:
                print('  err',e)
    con.close()
