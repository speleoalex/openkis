#!/usr/bin/env python3
import xml.etree.ElementTree as ET
import re

# Leggi il file KML
tree = ET.parse('/home/speleoalex/public_html/speleo/wish/web/wish/regioni-italiane.kml')
root = tree.getroot()

# Namespace KML
ns = {'kml': 'http://www.opengis.net/kml/2.2'}

# Raccogli tutte le coordinate delle regioni
regions = []
min_lat, max_lat = 90, 0
min_lon, max_lon = 180, 0

for placemark in root.findall('.//kml:Placemark', ns):
    name_elem = placemark.find('kml:name', ns)
    if name_elem is not None:
        name = name_elem.text
        if not name:
            continue
        if name == 'toscana':
            name = 'Toscana'  # Normalizza il nome
        # Rimuovi possibili spazi extra
        name = name.strip()

        # Trova TUTTE le coordinate (può esserci MultiGeometry)
        all_coords = []
        for coords_elem in placemark.findall('.//kml:coordinates', ns):
            if coords_elem is not None and coords_elem.text:
                coords_text = coords_elem.text.strip()
                # Parse coordinate (lon,lat,alt)
                coords = []
                for coord in coords_text.split():
                    if coord.strip():
                        parts = coord.strip().split(',')
                        if len(parts) >= 2:
                            try:
                                lon, lat = float(parts[0]), float(parts[1])
                                coords.append((lon, lat))
                                min_lat = min(min_lat, lat)
                                max_lat = max(max_lat, lat)
                                min_lon = min(min_lon, lon)
                                max_lon = max(max_lon, lon)
                            except ValueError:
                                pass  # Ignora coordinate non valide

                if coords:
                    all_coords.append(coords)

        if all_coords:
            regions.append({'name': name, 'polygons': all_coords})

# Calcola le dimensioni del SVG
width = 800
height = 1000
padding = 20

# Funzione di conversione coordinate geografiche -> SVG
def geo_to_svg(lon, lat):
    x = padding + (lon - min_lon) / (max_lon - min_lon) * (width - 2 * padding)
    # Inverti Y perché SVG ha origine in alto a sinistra
    y = height - padding - (lat - min_lat) / (max_lat - min_lat) * (height - 2 * padding)
    return x, y

# Genera SVG
print(f'<?xml version="1.0" encoding="UTF-8"?>')
print(f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {width} {height}" width="100%">')
print('  <style>')
print('    .regione {')
print('      fill: #cccccc;')
print('      stroke: #333;')
print('      stroke-width: 1.5;')
print('      cursor: pointer;')
print('      transition: opacity 0.3s;')
print('    }')
print('    .regione:hover {')
print('      opacity: 0.7;')
print('    }')
print('    .regione-verde { fill: #28a745; }')
print('    .regione-giallo { fill: #ffc107; }')
print('    .regione-arancione { fill: #fd7e14; }')
print('    .regione-rosso { fill: #dc3545; }')
print('  </style>')

# Genera path per ogni regione
for region in regions:
    name = region['name']
    polygons = region['polygons']

    # Genera path data per tutti i poligoni
    path_data = ''
    for coords in polygons:
        if len(coords) < 3:
            continue  # Salta poligoni con meno di 3 punti

        # Converti coordinate
        svg_coords = [geo_to_svg(lon, lat) for lon, lat in coords]

        # Aggiungi il poligono al path
        if path_data:
            path_data += ' '  # Separa i poligoni
        path_data += f'M {svg_coords[0][0]:.2f} {svg_coords[0][1]:.2f}'
        for x, y in svg_coords[1:]:
            path_data += f' L {x:.2f} {y:.2f}'
        path_data += ' Z'  # Chiudi il poligono

    if path_data:
        print(f'  <path id="{name}" class="regione" d="{path_data}"/>')

print('</svg>')
