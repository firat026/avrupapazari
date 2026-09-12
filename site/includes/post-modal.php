<?php
declare(strict_types=1);
// Post-ad modal: category -> country + city -> Dutch plate (kenteken) lookup via RDW open data.
$navCountries = [];
$citiesJson = '{}';
try {
    $nameCol = ['tr' => 'name_tr', 'nl' => 'name_nl', 'en' => 'name_en', 'de' => 'name_en'][$lang] ?? 'name_en';
    $navCountries = getDB()->query("SELECT id, code, {$nameCol} AS name FROM countries WHERE is_active = 1 ORDER BY sort_order ASC, id ASC")->fetchAll();
    $grouped = [];
    foreach (getDB()->query("SELECT name, country_id FROM cities WHERE is_active = 1 ORDER BY name ASC") as $city) {
        $grouped[(int)$city['country_id']][] = $city['name'];
    }
    $citiesJson = json_encode($grouped, JSON_UNESCAPED_UNICODE) ?: '{}';
} catch (Throwable $e) {}
?>
<div class="iv-overlay" id="ivOverlay">
    <div class="iv-modal">
        <div class="iv-header">
            <span class="iv-title"><?= t('nav.post_ad') ?></span>
            <button class="iv-close" id="ivClose"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
        </div>
        <div class="iv-body">
            <div class="iv-steps">
                <div class="iv-step-dot active" id="ivDot1"></div>
                <div class="iv-step-dot" id="ivDot2"></div>
                <div class="iv-step-dot" id="ivDot3"></div>
            </div>
            <button class="iv-back" id="ivBack"><svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg> <?= t('common.back') ?></button>

            <!-- STEP 1: Kategori -->
            <div id="ivStep1">
                <p style="font-size:.88rem;color:var(--text-2);margin-bottom:20px;font-weight:500"><?= t('modal.which_category') ?></p>
                <div class="iv-cat-grid" id="ivCatGrid">
                    <?php foreach($navCategories as $cat): ?>
                    <div class="iv-cat-item" data-module="<?= htmlspecialchars($cat['module']) ?>" data-id="<?= $cat['id'] ?>">
                        <div class="iv-cat-icon"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="4"/></svg></div>
                        <div class="iv-cat-name"><?= htmlspecialchars($cat['name']??$cat['module']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- STEP 2: Ülke + Şehir + Plaka -->
            <div id="ivStep2" style="display:none">
                <label class="iv-label"><?= t('listing.country') ?></label>
                <select class="iv-select" id="ivCountrySelect">
                    <option value=""><?= t('post.select_country') ?>...</option>
                    <?php foreach($navCountries as $c): ?>
                    <option value="<?= htmlspecialchars($c['code'] ?? '') ?>" data-id="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <div id="ivCityWrap" style="display:none">
                    <label class="iv-label"><?= t('listing.city') ?></label>
                    <select class="iv-select" id="ivCitySelect">
                        <option value=""><?= t('post.select_city') ?>...</option>
                    </select>
                </div>

                <!-- Plaka (araç + NL) -->
                <div class="iv-plaka-section" id="ivPlakaSection">
                    <div class="iv-plaka-title"><?= t('modal.plate_autofill') ?></div>
                    <div class="iv-plaka-desc"><?= t('modal.plate_desc') ?></div>
                    <div class="iv-plaka-box">
                        <div class="iv-plaka-flag">
                            <span class="iv-plaka-flag-stars">★ ★ ★</span>
                            <span>NL</span>
                        </div>
                        <input type="text" class="iv-plaka-input" id="ivPlakaInput" placeholder="XX-999-X" maxlength="9">
                    </div>
                    <div class="iv-plaka-actions">
                        <button type="button" class="iv-plaka-btn" id="ivPlakaBtn"><?= t('modal.plate_lookup') ?></button>
                        <button type="button" class="iv-plaka-skip" id="ivPlakaSkip"><?= t('modal.skip') ?></button>
                    </div>
                    <div class="iv-loading" id="ivPlakaLoading"><span class="iv-spinner"></span> <?= t('vehicle.lookup_loading') ?></div>
                    <div class="iv-plaka-error" id="ivPlakaError"><?= t('vehicle.lookup_not_found') ?></div>

                    <div class="iv-plaka-result" id="ivPlakaResult">
                        <div class="iv-car-card">
                            <div class="iv-car-header">
                                <div class="iv-car-title" id="ivCarTitle"></div>
                                <span class="iv-car-badge" id="ivCarBadge"><?= t('modal.rdw_verified') ?></span>
                            </div>
                            <div class="iv-car-specs">
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('vehicle.brand') ?></div><div class="iv-car-spec-value" id="ivCarMerk"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('vehicle.model') ?></div><div class="iv-car-spec-value" id="ivCarModel"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('vehicle.build_year') ?></div><div class="iv-car-spec-value" id="ivCarYear"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('vehicle.color') ?></div><div class="iv-car-spec-value" id="ivCarColor"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('vehicle.fuel') ?></div><div class="iv-car-spec-value" id="ivCarFuel"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('vehicle.power') ?></div><div class="iv-car-spec-value" id="ivCarPower"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('modal.engine_cc') ?></div><div class="iv-car-spec-value" id="ivCarEngine"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('modal.body_type') ?></div><div class="iv-car-spec-value" id="ivCarBody"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('modal.weight') ?></div><div class="iv-car-spec-value" id="ivCarWeight"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('vehicle.doors') ?></div><div class="iv-car-spec-value" id="ivCarDoors"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('modal.seats') ?></div><div class="iv-car-spec-value" id="ivCarSeats"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('vehicle.apk') ?></div><div class="iv-car-spec-value" id="ivCarAPK"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('modal.catalog_price') ?></div><div class="iv-car-spec-value" id="ivCarPrice"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('modal.tow_weight') ?></div><div class="iv-car-spec-value" id="ivCarTow"></div></div>
                                <div class="iv-car-spec"><div class="iv-car-spec-label"><?= t('modal.first_reg') ?></div><div class="iv-car-spec-value" id="ivCarFirstReg"></div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" class="iv-continue" id="ivContinueBtn"><?= t('common.next') ?></button>
            </div>
        </div>
    </div>
</div>

<script>
var CITIES_DATA = <?= $citiesJson ?>;
var LANG_SELECT_CITY = '<?= t('post.select_city') ?>...';

(function(){

    var ivOverlay=document.getElementById('ivOverlay'),selectedCat=null,selectedCountry=null;
    function goPost(){var target=(selectedCat==='arac')?'<?= BASE_URL ?>/pages/post-vehicle.php':'<?= BASE_URL ?>/pages/post.php';if(!window.SITE_USER){closeIV();if(window.openAuth)window.openAuth('login');return;}window.location.href=target;}
    document.querySelectorAll('[data-open-post]').forEach(function(b){b.addEventListener('click',function(e){e.preventDefault();openIV();});});
    function openIV(){ivOverlay.classList.add('show');document.body.style.overflow='hidden';resetModal()}
    document.getElementById('ivClose').addEventListener('click',closeIV);
    ivOverlay.addEventListener('click',function(e){if(e.target===ivOverlay)closeIV()});
    function closeIV(){ivOverlay.classList.remove('show');document.body.style.overflow=''}

    function resetModal(){
        document.getElementById('ivStep1').style.display='block';
        document.getElementById('ivStep2').style.display='none';
        document.getElementById('ivDot1').classList.add('active');
        document.getElementById('ivDot2').classList.remove('active');
        document.getElementById('ivPlakaSection').classList.remove('show');
        document.getElementById('ivPlakaResult').classList.remove('show');
        document.getElementById('ivPlakaError').classList.remove('show');
        document.getElementById('ivContinueBtn').classList.remove('show');
        document.getElementById('ivCityWrap').style.display='none';
        document.getElementById('ivBack').classList.remove('show');
        document.querySelectorAll('.iv-cat-item').forEach(function(el){el.classList.remove('selected')});
        selectedCat=null;selectedCountry=null;
    }

    document.getElementById('ivBack').addEventListener('click',function(){
        document.getElementById('ivStep2').style.display='none';
        document.getElementById('ivStep1').style.display='block';
        document.getElementById('ivDot2').classList.remove('active');
        this.classList.remove('show');
        document.getElementById('ivPlakaSection').classList.remove('show');
        document.getElementById('ivPlakaResult').classList.remove('show');
        document.getElementById('ivContinueBtn').classList.remove('show');
    });

    document.getElementById('ivCatGrid').addEventListener('click',function(e){
        var item=e.target.closest('.iv-cat-item');if(!item)return;
        document.querySelectorAll('.iv-cat-item').forEach(function(el){el.classList.remove('selected')});
        item.classList.add('selected');
        selectedCat=item.dataset.module;
        setTimeout(function(){
            document.getElementById('ivStep1').style.display='none';
            document.getElementById('ivStep2').style.display='block';
            document.getElementById('ivDot2').classList.add('active');
            document.getElementById('ivBack').classList.add('show');
        },200);
    });

    document.getElementById('ivCountrySelect').addEventListener('change',function(){
        selectedCountry=this.value;
        var countryId=this.selectedOptions[0]?this.selectedOptions[0].dataset.id:'';
        var isArac=(selectedCat==='arac'||selectedCat==='vehicles'||selectedCat==='cars');
        var cityWrap=document.getElementById('ivCityWrap');
        var citySelect=document.getElementById('ivCitySelect');
        if(countryId&&CITIES_DATA[countryId]){
            citySelect.innerHTML='<option value="">'+LANG_SELECT_CITY+'</option>';
            CITIES_DATA[countryId].forEach(function(c){
                citySelect.innerHTML+='<option value="'+c+'">'+c+'</option>';
            });
            cityWrap.style.display='block';
        } else { cityWrap.style.display='none'; }
        if(isArac&&selectedCountry==='NL'){
            document.getElementById('ivPlakaSection').classList.add('show');
        } else {
            document.getElementById('ivPlakaSection').classList.remove('show');
        }
        if(selectedCountry) document.getElementById('ivContinueBtn').classList.add('show');
    });

    document.getElementById('ivPlakaSkip').addEventListener('click',function(){
        var countryOpt = document.getElementById('ivCountrySelect').selectedOptions[0];
        var countryId = countryOpt ? countryOpt.dataset.id : '';
        var city = document.getElementById('ivCitySelect').value;
        var vehicleData = { rdw_verified: false, vehicle_type: 'auto', brand: '', model: '', build_year: '', color: '', fuel_type: '', power_hp: '', doors: '', apk_tot: '', country_id: countryId, city_id: city, fuel_display: '' };
        localStorage.setItem('vehicleData', JSON.stringify(vehicleData));
        goPost();
    });

    document.getElementById('ivContinueBtn').addEventListener('click',function(){
        var fuelMap = {'benzine':'benzine','diesel':'diesel','elektriciteit':'elektrisch','lpg':'lpg'};
        var rdwRaw = document.getElementById('ivPlakaResult').dataset.json;
        var rdw = rdwRaw ? JSON.parse(rdwRaw) : null;
        var countryOpt = document.getElementById('ivCountrySelect').selectedOptions[0];
        var countryId = countryOpt ? countryOpt.dataset.id : '';
        var city = document.getElementById('ivCitySelect').value;
        var vehicleData = {
            rdw_verified: !!rdw, vehicle_type: 'auto',
            brand: rdw ? rdw.merk : '', model: rdw ? rdw.model : '',
            build_year: rdw ? rdw.year : '', color: rdw ? rdw.kleur : '',
            fuel_type: rdw && rdw.brandstof ? (rdw.brandstof.toLowerCase().includes('hybride') ? 'hybride' : (fuelMap[rdw.brandstof.toLowerCase()] || rdw.brandstof)) : '',
            power_hp: rdw && rdw.power ? Math.round(rdw.power * 1.36) : '',
            doors: rdw ? rdw.doors : '',
            apk_tot: document.getElementById('ivCarAPK').textContent !== '-' ? document.getElementById('ivCarAPK').textContent : '',
            country_id: countryId, city_id: city, fuel_display: rdw ? rdw.brandstof : ''
        };
        localStorage.setItem('vehicleData', JSON.stringify(vehicleData));
        goPost();
    });

    document.getElementById('ivPlakaBtn').addEventListener('click',queryRDW);
    document.getElementById('ivPlakaInput').addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();queryRDW()}});

    function queryRDW(){
        var plate=document.getElementById('ivPlakaInput').value.replace(/[\s\-]/g,'').toUpperCase();
        if(plate.length<5)return;
        var loading=document.getElementById('ivPlakaLoading'),result=document.getElementById('ivPlakaResult'),error=document.getElementById('ivPlakaError'),btn=document.getElementById('ivPlakaBtn');
        loading.classList.add('show');result.classList.remove('show');error.classList.remove('show');btn.disabled=true;
        Promise.all([
            fetch('https://opendata.rdw.nl/resource/m9d7-ebf2.json?kenteken='+plate).then(function(r){return r.json()}),
            fetch('https://opendata.rdw.nl/resource/8ys7-d773.json?kenteken='+plate).then(function(r){return r.json()})
        ]).then(function(results){
            loading.classList.remove('show');btn.disabled=false;
            var data=results[0],fuel=results[1];
            if(!data||data.length===0){error.classList.add('show');return}
            var car=data[0],f=fuel&&fuel.length>0?fuel[0]:{};
            var merk=car.merk||'',model=car.handelsbenaming||'',kleur=car.eerste_kleur||'';
            var datum=car.datum_eerste_toelating||'',year=datum?datum.substring(0,4):'',month=datum?datum.substring(4,6):'';
            var massa=car.massa_ledig_voertuig||'',body=car.inrichting||'',doors=car.aantal_deuren||'',seats=car.aantal_zitplaatsen||'';
            var apk=car.vervaldatum_apk||'',price=car.catalogusprijs||'',tow=car.maximum_massa_trekken_geremd||'';
            var brandstofList=fuel.map(function(x){return x.brandstof_omschrijving}).filter(Boolean);
            var brandstof=brandstofList.length>1?'Hybride ('+brandstofList.join(' / ')+')':brandstofList[0]||'-';
            var power=Math.max.apply(null,fuel.map(function(x){return parseFloat(x.nettomaximumvermogen||x.netto_max_vermogen_elektrisch||0)}))||'';
            var cc=car.cilinderinhoud||'';
            document.getElementById('ivCarTitle').textContent=merk+' '+model;
            document.getElementById('ivCarMerk').textContent=merk;
            document.getElementById('ivCarModel').textContent=model;
            document.getElementById('ivCarYear').textContent=year;
            document.getElementById('ivCarColor').textContent=kleur;
            document.getElementById('ivCarFuel').textContent=brandstof;
            document.getElementById('ivCarPower').textContent=power?(Math.round(power*1.36)+' HP / '+power+' kW'):'-';
            document.getElementById('ivCarEngine').textContent=cc?(cc+' cc'):'-';
            document.getElementById('ivCarBody').textContent=body||'-';
            document.getElementById('ivCarWeight').textContent=massa?(massa+' kg'):'-';
            document.getElementById('ivCarDoors').textContent=doors||'-';
            document.getElementById('ivCarSeats').textContent=seats||'-';
            var apkFormatted=apk?(apk.substring(6,8)+'.'+apk.substring(4,6)+'.'+apk.substring(0,4)):'-';
            document.getElementById('ivCarAPK').textContent=apkFormatted;
            document.getElementById('ivCarPrice').textContent=price?('\u20ac '+Number(price).toLocaleString('nl-NL')):'-';
            document.getElementById('ivCarTow').textContent=tow?(tow+' kg'):'-';
            var firstReg=datum?(datum.substring(6,8)+'.'+month+'.'+year):'-';
            document.getElementById('ivCarFirstReg').textContent=firstReg;
            result.dataset.json=JSON.stringify({merk:merk,model:model,year:year,kleur:kleur,brandstof:brandstof,power:power,doors:doors});
            result.classList.add('show');
        }).catch(function(){loading.classList.remove('show');btn.disabled=false;error.classList.add('show');});
    }
})();
</script>
