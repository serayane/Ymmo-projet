import mysql.connector
import matplotlib.pyplot as plt
import matplotlib.patches as mpatches
import pandas as pd
from datetime import datetime

# Connexion à la base de données
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="ymmo"
)

cursor = conn.cursor()

# 1. Prix moyen par type de bien
cursor.execute("SELECT type, ROUND(AVG(prix), 0) as prix_moyen, COUNT(*) as nombre FROM biens GROUP BY type ORDER BY prix_moyen DESC")
data_type = cursor.fetchall()
types = [row[0].capitalize() for row in data_type]
prix_moyens = [float(row[1]) for row in data_type]
nombres = [row[2] for row in data_type]

# 2. Biens par ville
cursor.execute("SELECT ville, COUNT(*) as nombre, ROUND(AVG(prix), 0) as prix_moyen FROM biens GROUP BY ville ORDER BY nombre DESC")
data_ville = cursor.fetchall()
villes = [row[0] for row in data_ville]
nb_villes = [row[1] for row in data_ville]

# 3. Biens les plus demandés
cursor.execute("""
    SELECT b.titre, b.ville, COUNT(d.id) as nb_demandes 
    FROM biens b 
    LEFT JOIN demandes d ON b.id = d.bien_id 
    GROUP BY b.id 
    ORDER BY nb_demandes DESC 
    LIMIT 5
""")
data_pop = cursor.fetchall()

# 4. Prix moyen global
cursor.execute("SELECT ROUND(AVG(prix), 0) as moyenne, MIN(prix) as min_prix, MAX(prix) as max_prix FROM biens")
data_global = cursor.fetchone()
prix_moyen_global = float(data_global[0])
prix_min = float(data_global[1])
prix_max = float(data_global[2])

# 5. Statut des biens
cursor.execute("SELECT statut, COUNT(*) as nombre FROM biens GROUP BY statut")
data_statut = cursor.fetchall()
statuts = [row[0].capitalize() for row in data_statut]
nb_statuts = [row[1] for row in data_statut]

conn.close()

# ─── CRÉATION DU RAPPORT MATPLOTLIB ───────────────────────────────────────
fig = plt.figure(figsize=(18, 22))
fig.patch.set_facecolor('#0A0F1E')

# Titre principal
fig.text(0.5, 0.97, 'YMMO — Analyse du Marché Immobilier',
         ha='center', va='top', fontsize=28, fontweight='bold',
         color='#C9A96E', fontfamily='serif')

fig.text(0.5, 0.945, f'Rapport généré le {datetime.now().strftime("%d/%m/%Y à %H:%M")} · Analyse Python + MySQL',
         ha='center', va='top', fontsize=13, color='#8892A4')

# ─── KPIs ───────────────────────────────────────────────────────────────────
ax_kpi = fig.add_axes([0.05, 0.86, 0.9, 0.07])
ax_kpi.set_facecolor('#1C2333')
ax_kpi.set_xlim(0, 4)
ax_kpi.set_ylim(0, 1)
ax_kpi.axis('off')

kpis = [
    ("Prix Moyen Global", f"{prix_moyen_global:,.0f} €".replace(',', ' ')),
    ("Prix Maximum", f"{prix_max:,.0f} €".replace(',', ' ')),
    ("Prix Minimum", f"{prix_min:,.0f} €".replace(',', ' ')),
    ("Nombre de Biens", str(sum(nombres))),
]

for i, (label, value) in enumerate(kpis):
    x = i + 0.5
    ax_kpi.text(x, 0.72, value, ha='center', va='center',
                fontsize=20, fontweight='bold', color='#C9A96E')
    ax_kpi.text(x, 0.25, label, ha='center', va='center',
                fontsize=11, color='#8892A4')
    if i < 3:
        ax_kpi.axvline(x=i+1, color='#252D40', linewidth=1)

ax_kpi.add_patch(mpatches.FancyBboxPatch((0, 0), 4, 1,
    boxstyle="round,pad=0", facecolor='#1C2333',
    edgecolor='#C9A96E', linewidth=1.5))

# ─── GRAPHIQUE 1 — Prix moyen par type ────────────────────────────────────
ax1 = fig.add_axes([0.05, 0.55, 0.42, 0.28])
ax1.set_facecolor('#1C2333')
fig.patch.set_facecolor('#0A0F1E')

bars = ax1.bar(types, prix_moyens, color='#C9A96E', alpha=0.85, width=0.5, edgecolor='#E8C98A', linewidth=0.8)
ax1.set_title('Prix Moyen par Type de Bien', color='#C9A96E', fontsize=14, fontweight='bold', pad=12)
ax1.set_xlabel('Type de bien', color='#8892A4', fontsize=11)
ax1.set_ylabel('Prix moyen (€)', color='#8892A4', fontsize=11)
ax1.tick_params(colors='#8892A4')
ax1.spines['bottom'].set_color('#252D40')
ax1.spines['left'].set_color('#252D40')
ax1.spines['top'].set_visible(False)
ax1.spines['right'].set_visible(False)
ax1.yaxis.grid(True, color='#252D40', linewidth=0.5)
ax1.set_axisbelow(True)

for bar, val in zip(bars, prix_moyens):
    ax1.text(bar.get_x() + bar.get_width()/2, bar.get_height() + 5000,
             f'{val:,.0f} €'.replace(',', ' '),
             ha='center', va='bottom', color='#F5F0E8', fontsize=10, fontweight='bold')

# ─── GRAPHIQUE 2 — Biens par ville ────────────────────────────────────────
ax2 = fig.add_axes([0.55, 0.55, 0.4, 0.28])
ax2.set_facecolor('#1C2333')

colors_pie = ['#C9A96E', '#A07840', '#7A5C30', '#554020', '#3A2C18']
wedges, texts, autotexts = ax2.pie(
    nb_villes, labels=villes,
    colors=colors_pie[:len(villes)],
    autopct='%1.0f%%',
    startangle=90,
    pctdistance=0.75,
    wedgeprops={'edgecolor': '#0A0F1E', 'linewidth': 2}
)
for text in texts:
    text.set_color('#F5F0E8')
    text.set_fontsize(11)
for autotext in autotexts:
    autotext.set_color('#0A0F1E')
    autotext.set_fontweight('bold')
    autotext.set_fontsize(11)

ax2.set_title('Répartition des Biens par Ville', color='#C9A96E', fontsize=14, fontweight='bold', pad=12)

# ─── GRAPHIQUE 3 — Biens populaires ───────────────────────────────────────
ax3 = fig.add_axes([0.05, 0.25, 0.55, 0.25])
ax3.set_facecolor('#1C2333')

titres_pop = [f"{row[0][:30]}..." if len(row[0]) > 30 else row[0] for row in data_pop]
nb_demandes = [row[2] for row in data_pop]

bars3 = ax3.barh(titres_pop, nb_demandes, color='#C9A96E', alpha=0.85, edgecolor='#E8C98A', linewidth=0.8)
ax3.set_title('Biens les Plus Demandés', color='#C9A96E', fontsize=14, fontweight='bold', pad=12)
ax3.set_xlabel("Nombre de demandes de visite", color='#8892A4', fontsize=11)
ax3.tick_params(colors='#8892A4', labelsize=10)
ax3.spines['bottom'].set_color('#252D40')
ax3.spines['left'].set_color('#252D40')
ax3.spines['top'].set_visible(False)
ax3.spines['right'].set_visible(False)
ax3.xaxis.grid(True, color='#252D40', linewidth=0.5)
ax3.set_axisbelow(True)
ax3.invert_yaxis()

for bar, val in zip(bars3, nb_demandes):
    ax3.text(bar.get_width() + 0.05, bar.get_y() + bar.get_height()/2,
             str(val), va='center', color='#F5F0E8', fontsize=11, fontweight='bold')

# ─── GRAPHIQUE 4 — Statut des biens ───────────────────────────────────────
ax4 = fig.add_axes([0.68, 0.25, 0.28, 0.25])
ax4.set_facecolor('#1C2333')

colors_statut = ['#2ECC71', '#E74C3C', '#C9A96E']
wedges4, texts4, autotexts4 = ax4.pie(
    nb_statuts, labels=statuts,
    colors=colors_statut[:len(statuts)],
    autopct='%1.0f%%',
    startangle=90,
    wedgeprops={'edgecolor': '#0A0F1E', 'linewidth': 2}
)
for text in texts4:
    text.set_color('#F5F0E8')
    text.set_fontsize(12)
for autotext in autotexts4:
    autotext.set_color('#0A0F1E')
    autotext.set_fontweight('bold')

ax4.set_title('Statut des Biens', color='#C9A96E', fontsize=14, fontweight='bold', pad=12)

# ─── ANALYSE TEXTUELLE ────────────────────────────────────────────────────
ax5 = fig.add_axes([0.05, 0.05, 0.9, 0.17])
ax5.set_facecolor('#1C2333')
ax5.axis('off')
ax5.add_patch(mpatches.FancyBboxPatch((0, 0), 1, 1,
    boxstyle="round,pad=0", facecolor='#1C2333',
    edgecolor='#C9A96E', linewidth=1.5, transform=ax5.transAxes))

type_plus_cher = types[0] if types else "N/A"
ville_plus_active = villes[0] if villes else "N/A"
bien_plus_demande = data_pop[0][0] if data_pop else "N/A"

analyse = f"""📊  ANALYSE & RECOMMANDATIONS STRATÉGIQUES

▶  Le type de bien le plus cher est : {type_plus_cher} avec un prix moyen de {prix_moyens[0]:,.0f} €
▶  La ville la plus active est : {ville_plus_active} avec {nb_villes[0]} bien(s) disponible(s)
▶  Le bien le plus demandé est : {bien_plus_demande}
▶  Écart de prix entre le moins cher et le plus cher : {prix_max - prix_min:,.0f} €

💡  Recommandations : Concentrer les efforts commerciaux sur {ville_plus_active} · Développer l'offre de type {type_plus_cher} · Prix moyen du marché : {prix_moyen_global:,.0f} €
""".replace(',', ' ')

ax5.text(0.02, 0.85, analyse, transform=ax5.transAxes,
         fontsize=12, color='#8892A4', va='top',
         linespacing=1.8, fontfamily='monospace')

# Sauvegarde
plt.savefig('C:/xampp/htdocs/ymmo/analyse_ymmo.png',
            dpi=150, bbox_inches='tight',
            facecolor='#0A0F1E', edgecolor='none')

print("✅ Rapport généré : analyse_ymmo.png")
print(f"Prix moyen global : {prix_moyen_global:,.0f} €")
print(f"Ville la plus active : {ville_plus_active}")
print(f"Type le plus cher : {type_plus_cher}")