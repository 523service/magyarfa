# MagyarSzigeteles.hu – Fejlesztési Roadmap & Checklist
_Award-grade Laravel + Filament webshop_

---

## 🟦 SZAKASZ 1 – ALAPRENDSZER (FOUNDATION)

**Cél:** Stabil, hosszú távon karbantartható alap, Filament admin + frontend skeleton  
**Idő:** ~20–28 óra

- [X] **MSZ-101** Laravel 12 projekt létrehozása
- [X] **MSZ-102** Filament v4 telepítése és konfigurálása
- [X] **MSZ-103** Admin jogosultsági rendszer (`is_admin`)
- [X] **MSZ-104** Git repository + branch stratégia
- [ ] **MSZ-105** Deploy workflow (GitHub Actions)
- [X] **MSZ-106** Környezeti változók, `.env` sablon
- [ ] **MSZ-107** Alap SEO infrastruktúra (meta, sitemap, robots)
- [ ] **MSZ-108** Maintenance / Coming Soon oldal

---

## 🟩 SZAKASZ 2 – FRONTEND TÉMA & UI ALAPOK

**Cél:** Letisztult, modern, díjnyertes vizuális alap  
**Idő:** ~25–35 óra

- [X] **MSZ-201** Frontend layout (header / footer / main layout)
- [X] **MSZ-202** Bal oldali kategória sidebar (accordion menü)
- [ ] **MSZ-203** Hero szekció + USP blokkok
- [X] **MSZ-204** Termékkártya komponens (modern UI)
- [X] **MSZ-205** Terméklista rács + toolbar
- [X] **MSZ-206** Reszponzív mobil navigáció
- [ ] **MSZ-207** Micro-UX elemek (hover, transition, skeleton)
- [ ] **MSZ-208** Design system alap (színek, spacing, typography)

---

## 🟨 SZAKASZ 3 – ADATBÁZIS & TERMÉKSTRUKTÚRA

**Cél:** Építőanyag-specifikus, skálázható adatmodell  
**Idő:** ~40–50 óra

- [X] **MSZ-301** Kategóriafa (`categories`) – hierarchikus
- [X] **MSZ-302** Termék modell (`products`)
- [ ] **MSZ-303** Termék variációk (`product_variants`)
- [ ] **MSZ-304** Műszaki attribútumok (λ, vastagság, m²)
- [X] **MSZ-305** Gyártók (`brands`)
- [ ] **MSZ-306** Fogalomtár (`glossary_terms`)
- [ ] **MSZ-307** Készletkezelés (`stock`)
- [ ] **MSZ-308** Média + PDF adatlap kezelés
- [X] **MSZ-309** Filament termékkezelő resource

---

## 🟧 SZAKASZ 4 – KOSÁR & CHECKOUT

**Cél:** Súly- és térfogatérzékeny, profi vásárlási folyamat  
**Idő:** ~55–70 óra

- [X] **MSZ-401** Kosár rendszer (vendég módban is)
- [ ] **MSZ-402** m² → csomag automatikus kalkulátor
- [ ] **MSZ-403** Ajánlott kiegészítők logika
- [ ] **MSZ-404** Szállítási díj kalkulátor (raklap / térfogat)
- [X] **MSZ-405** Checkout – lépéses folyamat
- [ ] **MSZ-406** Online fizetés (Stripe / Barion)
- [ ] **MSZ-407** Előre utalás / utánvét
- [ ] **MSZ-408** Számlázz.hu díjbekérő integráció
- [ ] **MSZ-409** Rendelés admin kezelő (Filament)

---

## 🟦 SZAKASZ 5 – FELHASZNÁLÓI RENDSZER

**Cél:** Profi ügyfélélmény, visszatérő vásárlók  
**Idő:** ~30–38 óra

- [X] **MSZ-501** Laravel Breeze (Blade) telepítése
- [ ] **MSZ-502** Social login (Google)
- [ ] **MSZ-503** Social login (Facebook)
- [X] **MSZ-504** Profil oldal – alapadatok
- [X] **MSZ-505** Szállítási címek kezelése
- [X] **MSZ-506** Számlázási címek kezelése
- [ ] **MSZ-507** Rendelési előzmények
- [ ] **MSZ-508** Kedvencek / wishlist

---

## 🟥 SZAKASZ 6 – HIGH-TECH UX & ÉRTÉKNÖVELŐ FUNKCIÓK

**Cél:** Piacvezető élmény, díjra érdemes UX  
**Idő:** ~50–70 óra

- [ ] **MSZ-601** Hőszigetelés kalkulátor (homlokzat, födém)
- [ ] **MSZ-602** Ragasztó / háló / dübel kalkuláció
- [ ] **MSZ-603** Ajánlatkérő modul (admin státuszokkal)
- [ ] **MSZ-604** Gyorskereső (autocomplete)
- [ ] **MSZ-605** Termék összehasonlító
- [ ] **MSZ-606** Utoljára megtekintett termékek
- [ ] **MSZ-607** Okos ajánlórendszer
- [ ] **MSZ-608** Raklap kalkulátor
- [ ] **MSZ-609** PageSpeed optimalizáció
- [ ] **MSZ-610** Rich Snippet / Schema.org
- [ ] **MSZ-611** Quickview modal for product variants (and product infos)

---

## 🟪 SZAKASZ 7 – MARKETING, JOGI & SEO

**Cél:** Jogilag tiszta, keresőoptimalizált jelenlét  
**Idő:** ~15–25 óra

- [ ] **MSZ-701** Hírlevél feliratkozás
- [ ] **MSZ-702** Popup / exit intent
- [ ] **MSZ-703** ÁSZF oldal
- [ ] **MSZ-704** GDPR / adatkezelési tájékoztató
- [ ] **MSZ-705** Cookie consent banner
- [ ] **MSZ-706** Rólunk oldal
- [ ] **MSZ-707** Impresszum
- [ ] **MSZ-708** SEO audit (Lighthouse)

---

## 🟫 SZAKASZ 8 – TESZTELÉS & FINISH

**Cél:** Éles indulás, pályázatra kész minőség  
**Idő:** ~15–25 óra

- [ ] **MSZ-801** Mobil & cross-browser teszt
- [ ] **MSZ-802** Checkout edge-case tesztelés
- [ ] **MSZ-803** E-mail sablonok ellenőrzése
- [ ] **MSZ-804** Admin QA
- [ ] **MSZ-805** Demo tartalom feltöltés
- [ ] **MSZ-806** Production release

---

## 🟫 FONTOS SZAKASZ

**Cél:** Ezt fontos még

- [ ] **MSZ-901** Szűrő (EAV alapján)
- [ ] **MSZ-902** Anyagmennyiség kalkulátor (thermodam alapján)

## ÖSSZESÍTÉS

---

_Dokumentum verzió: v1.2_  
_A projekt célja: Év Honlapja pályázatra alkalmas, piacvezető minőség_
