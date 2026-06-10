# Infrastructure Ymmo

## Architecture
- Windows Server 2025 — contrôleur de domaine
- Domaine : ymmo.local
- Routeur OPNsense — LAN 192.168.13.254
- VM Kali Linux — poste agence simulé

## Services configurés
- Active Directory (AD DS)
- DNS
- DHCP — plage 192.168.13.100 à 192.168.13.150
- GPO : Politique-Ymmo
- OUs : Ymmo / Siege / Agences

## Plan d'adressage IP
| Élément | IP |
|---|---|
| Serveur Windows | 192.168.13.20 |
| Routeur OPNsense LAN | 192.168.13.254 |
| Poste agence (Kali) | 192.168.13.100 |
| Plage DHCP | 192.168.13.100 - 150 |

## Utilisateurs AD
- Jean Dupont (jdupont) — agent du siège