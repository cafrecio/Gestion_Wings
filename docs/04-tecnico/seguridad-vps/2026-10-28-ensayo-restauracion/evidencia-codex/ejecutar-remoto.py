import sys,subprocess,re,time,json
from pathlib import Path
from datetime import datetime,timezone
ROOT=Path(__file__).resolve().parents[5]
HERE=Path(__file__).resolve().parent
SSH=r'C:\Windows\System32\OpenSSH\ssh.exe'
def sanitize(s):
 s=re.sub(r'base64:[A-Za-z0-9+/=]{12,}','[CLAVE OMITIDA]',s)
 s=re.sub(r'(?i)(sha256:)[A-Za-z0-9+/=]{12,}','[HUELLA OMITIDA]',s)
 s=re.sub(r'(?i)((?:password|client_secret|access_token|refresh_token|APP_KEY)\s*[=:]\s*)[^\s,;]+',r'\1[OMITIDO]',s)
 s=re.sub(r'\b[a-fA-F0-9]{64}\b','[HUELLA OMITIDA]',s)
 return s
if __name__=='__main__':
 script=Path(sys.argv[1]);name=sys.argv[2];start=time.monotonic();at=datetime.now(timezone.utc).isoformat()
 p=subprocess.run([SSH,'-T','vps','bash','-s'],input=(script.read_text(encoding='utf-8-sig').replace('\r\n','\n').rstrip()+'\n').encode(),capture_output=True)
 out=sanitize(p.stdout.decode('utf-8',errors='replace'));err=sanitize(p.stderr.decode('utf-8',errors='replace'))
 (HERE/(name+'.txt')).write_text(out,encoding='utf-8');(HERE/(name+'.stderr.txt')).write_text(err,encoding='utf-8')
 info={'inicio_UTC':at,'segundos':round(time.monotonic()-start,2),'codigo':p.returncode,'salida':name+'.txt','error':name+'.stderr.txt'}
 (HERE/(name+'.json')).write_text(json.dumps(info,indent=2),encoding='utf-8');print(json.dumps(info));print(out[-8500:]);print(err[-800:]);sys.exit(p.returncode)
