// Функции для работы с детальной формой пользователя

// Глобальные переменные для умной валидации
var originalFormData = {};
var changedFields = {};

// Инициализация отслеживания изменений
function initChangeTracking(data) {
    // Сохраняем исходные данные
    originalFormData = {
        user_email: data.user?.email || '',
        user_phone: data.user?.phone || '',
        user_is_admin: data.user?.is_admin || '0',
        name: data.company?.name || '',
        tax_id: data.company?.tax_id || '',
        legal_name: data.company?.legal_name || '',
        start_date: data.company?.start_date || '',
        website: data.company?.website || '',
        organization_type: data.company?.organization_type || '',
        main_activity: data.company?.main_activity || '',
        main_product: {
            name: data.products?.main?.name || '',
            description: data.products?.main?.description || '',
            annual_export: data.products?.main?.annual_export || '',
            certifications: data.products?.main?.certifications || ''
        },
        current_markets: data.company_data?.current_markets || ''
    };
    
    // Если нет данных компании, не инициализируем отслеживание для полей компании
    if (!data.has_company_data) {
        // Инициализируем только для базовых полей пользователя
        setTimeout(() => {
            setupChangeTracking();
        }, 150);
        return;
    }
    
    // Сбрасываем отслеживание изменений
    changedFields = {};
    
        // Убеждаемся, что dropdown'ы правильно заполнены значениями из БД
        setTimeout(() => {
            const isAdminField = document.getElementById('form_user_is_admin');
            if (isAdminField && originalFormData.user_is_admin !== undefined) {
                isAdminField.value = String(originalFormData.user_is_admin);
            }
            
            const orgTypeField = document.getElementById('form_organization_type');
            if (orgTypeField && originalFormData.organization_type) {
                orgTypeField.value = originalFormData.organization_type;
            }
            
            const mainActivityField = document.getElementById('form_main_activity');
            if (mainActivityField && originalFormData.main_activity) {
                mainActivityField.value = originalFormData.main_activity;
            }
            
            const currentMarketsField = document.getElementById('form_current_markets');
            if (currentMarketsField && originalFormData.current_markets) {
                currentMarketsField.value = originalFormData.current_markets;
            }
            
            // Добавляем обработчики событий для всех редактируемых полей
            setupChangeTracking();
        }, 150);
}

// Настройка отслеживания изменений полей
function setupChangeTracking() {
    // Основные текстовые поля
    const textFields = [
        'form_user_email', 'form_user_phone',
        'form_name', 'form_tax_id', 'form_legal_name', 'form_start_date', 'form_website',
        'form_main_product_name', 'form_main_product_description',
        'form_main_product_annual_export', 'form_certifications'
    ];
    
    textFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            // Сохраняем исходное значение для сравнения (из originalFormData)
            const originalValue = getOriginalValue(fieldId);
            
            // Добавляем обработчик (без клонирования, чтобы не потерять значение)
            field.addEventListener('input', function() {
                if (this.value.trim() !== originalValue) {
                    markFieldChanged(fieldId);
                } else {
                    // Если вернули исходное значение, убираем из измененных
                    delete changedFields[fieldId];
                }
            });
            field.addEventListener('change', function() {
                if (this.value.trim() !== originalValue) {
                    markFieldChanged(fieldId);
                } else {
                    delete changedFields[fieldId];
                }
            });
        }
    });
    
    // Dropdown поля (select)
    const selectFields = [
        'form_user_is_admin',
        'form_organization_type', 'form_main_activity', 'form_current_markets'
    ];
    
    selectFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            // Сохраняем исходное значение для сравнения (из originalFormData)
            const originalValue = getOriginalValue(fieldId);
            
            // Добавляем обработчик
            field.addEventListener('change', function() {
                if (this.value !== originalValue) {
                    markFieldChanged(fieldId);
                } else {
                    delete changedFields[fieldId];
                }
            });
        }
    });
    
}

// Помечает поле как измененное
function markFieldChanged(fieldId) {
    changedFields[fieldId] = true;
}

// Проверяет, было ли поле изменено
function isFieldChanged(fieldId) {
    return changedFields[fieldId] === true;
}

// Получает исходное значение поля из originalFormData
function getOriginalValue(fieldId) {
        const fieldMap = {
        'form_user_email': 'user_email',
        'form_user_phone': 'user_phone',
        'form_user_is_admin': 'user_is_admin',
        'form_name': 'name',
        'form_tax_id': 'tax_id',
        'form_legal_name': 'legal_name',
        'form_start_date': 'start_date',
        'form_website': 'website',
        'form_organization_type': 'organization_type',
        'form_main_activity': 'main_activity',
        'form_main_product_name': 'main_product.name',
        'form_main_product_description': 'main_product.description',
        'form_main_product_annual_export': 'main_product.annual_export',
        'form_certifications': 'main_product.certifications',
        'form_current_markets': 'current_markets'
    };
    
    const path = fieldMap[fieldId];
    if (!path) return '';
    
    const parts = path.split('.');
    let value = originalFormData;
    for (const part of parts) {
        if (value && typeof value === 'object' && part in value) {
            value = value[part];
        } else {
            return '';
        }
    }
    
    return value || '';
}

// Получает значение поля (текущее или исходное)
function getFieldValue(fieldId, originalValue) {
    if (isFieldChanged(fieldId)) {
        const field = document.getElementById(fieldId);
        if (field) {
            if (field.tagName === 'SELECT') {
                return field.value || '';
            }
            return field.value.trim();
        }
        return '';
    }
    return originalValue || '';
}

function generateUserFormHTML(data, userId) {
    const user = data.user || {};
    const hasCompanyData = data.has_company_data === true; // Явно проверяем, что это true
    const company = data.company || {};
    const addresses = data.addresses || {};
    const contacts = data.contacts || {};
    const socialNetworks = data.social_networks || [];
    const products = data.products || {};
    const services = data.services || {};
    const companyData = data.company_data || {};
    const files = data.files || {};
    
    let html = '<form id="user_detail_form_content" onsubmit="return false;">';
    html += '<input type="hidden" id="form_user_id" value="' + userId + '">';
    
    // Секция 0: Datos Básicos (всегда показываем)
    html += '<div class="user-form-section">';
    html += '<h4 class="section-title">0. Datos Básicos</h4>';
    
    html += '<div class="form-group"><label>Correo electrónico <span class="req">*</span></label>';
    html += '<input type="email" class="form-control" id="form_user_email" value="' + escapeHtml(user.email || '') + '" required></div>';
    
    html += '<div class="form-group"><label>Teléfono <span class="req">*</span></label>';
    html += '<input type="text" class="form-control" id="form_user_phone" value="' + escapeHtml(user.phone || '') + '" required></div>';
    
    html += '<div class="form-group"><label>Es Administrador <span class="req">*</span></label>';
    html += '<select class="form-control" id="form_user_is_admin" required>';
    html += '<option value="0"' + (user.is_admin == 0 ? ' selected' : '') + '>No</option>';
    html += '<option value="1"' + (user.is_admin == 1 ? ' selected' : '') + '>Sí</option>';
    html += '</select></div>';
    
    html += '<div class="form-group"><label>Creado el</label>';
    html += '<div class="readonly-field">' + escapeHtml(user.created_at || '') + '</div></div>';
    
    html += '<div class="form-group"><label>Actualizado el</label>';
    html += '<div class="readonly-field">' + escapeHtml(user.updated_at || '') + '</div></div>';
    
    html += '</div>';
    
    // Если нет данных компании, показываем сообщение и не показываем остальные секции
    if (!hasCompanyData) {
        html += '<div class="user-form-section">';
        html += '<div class="alert alert-info">Este usuario aún no ha completado el registro completo.</div>';
        html += '</div>';
        
        // Кнопка Guardar (только для Datos Básicos)
        html += '<div class="form-actions">';
        html += '<button type="button" class="btn btn-primary" onclick="saveUserBasicData(' + userId + ')">Guardar</button>';
        html += '<div id="save_message" style="margin-top: 10px;"></div>';
        html += '</div>';
        
        html += '</form>';
        
        return html;
    }
    
    // Статус модерации
    const moderationStatus = company.moderation_status || 'pending';
    const isApproved = moderationStatus === 'approved';
    const statusText = isApproved ? 'Aprobado' : 'En moderación';
    const statusClass = isApproved ? 'alert-success' : 'alert-warning';
    
    html += '<div class="user-form-section">';
    html += '<div class="alert ' + statusClass + '" style="margin-bottom: 20px;">';
    html += '<strong>Estado de moderación:</strong> ' + statusText;
    html += '</div>';
    html += '</div>';
    
    // Секция 1: Datos de la Empresa (только если есть данные компании)
    html += '<div class="user-form-section">';
    html += '<h4 class="section-title">1. Datos de la Empresa</h4>';
    
    html += '<div class="form-group"><label>Nombre de la Empresa <span class="req">*</span></label>';
    html += '<input type="text" class="form-control" id="form_name" value="' + escapeHtml(company.name || '') + '" required></div>';
    
    html += '<div class="form-group"><label>CUIT / Identificación Fiscal <span class="req">*</span></label>';
    html += '<input type="text" class="form-control" id="form_tax_id" value="' + escapeHtml(company.tax_id || '') + '" required></div>';
    
    html += '<div class="form-group"><label>Razón social <span class="req">*</span></label>';
    html += '<input type="text" class="form-control" id="form_legal_name" value="' + escapeHtml(company.legal_name || '') + '" required></div>';
    
    html += '<div class="form-group"><label>Fecha de Inicio de Actividad <span class="req">*</span></label>';
    html += '<input type="text" class="form-control" id="form_start_date" value="' + escapeHtml(company.start_date || '') + '" placeholder="dd/mm/yyyy" required></div>';
    
    html += '<div class="form-group"><label>Página web</label>';
    html += '<input type="text" class="form-control" id="form_website" value="' + escapeHtml(company.website || '') + '"></div>';
    
    // Redes sociales (только чтение)
    if (socialNetworks.length > 0) {
        html += '<div class="form-group"><label>Redes sociales</label>';
        html += '<div class="readonly-field">' + socialNetworks.map(sn => escapeHtml(sn.network_type || '') + ': ' + escapeHtml(sn.url || '')).join(', ') + '</div></div>';
    }
    
    // Domicilio Legal (только чтение)
    const legalAddr = addresses.legal || {};
    if (legalAddr.street) {
        html += '<div class="form-group"><label>Domicilio Legal</label>';
        html += '<div class="readonly-field">';
        html += escapeHtml(legalAddr.street || '') + ' ' + escapeHtml(legalAddr.street_number || '') + ', ';
        html += escapeHtml(legalAddr.locality || '') + ', ' + escapeHtml(legalAddr.department || '');
        html += '</div></div>';
    }
    
    // Dirección administrativa (только чтение)
    const adminAddr = addresses.admin || {};
    if (adminAddr.street) {
        html += '<div class="form-group"><label>Dirección administrativa</label>';
        html += '<div class="readonly-field">';
        html += escapeHtml(adminAddr.street || '') + ' ' + escapeHtml(adminAddr.street_number || '') + ', ';
        html += escapeHtml(adminAddr.locality || '') + ', ' + escapeHtml(adminAddr.department || '');
        html += '</div></div>';
    }
    
    // Persona de Contacto (только чтение)
    if (contacts.contact_person) {
        html += '<div class="form-group"><label>Persona de Contacto</label>';
        html += '<div class="readonly-field">';
        html += escapeHtml(contacts.contact_person || '') + ' (' + escapeHtml(contacts.position || '') + '), ';
        html += escapeHtml(contacts.email || '') + ', ' + escapeHtml(contacts.area_code || '') + ' ' + escapeHtml(contacts.phone || '');
        html += '</div></div>';
    }
    
    html += '</div>';
    
    // Секция 2: Clasificación
    html += '<div class="user-form-section">';
    html += '<h4 class="section-title">2. Clasificación de la Empresa</h4>';
    
    html += '<div class="form-group"><label>Tipo de Organización <span class="req">*</span></label>';
    html += '<select class="form-control" id="form_organization_type" required>';
    html += '<option value="">...</option>';
    const orgTypes = ['Empresa grande', 'PyME', 'Cooperativa', 'Emprendimiento', 'Startup', 'Clúster', 'Consorcio', 'Otros (especificar)'];
    orgTypes.forEach(type => {
        html += '<option value="' + escapeHtml(type) + '"' + (company.organization_type === type ? ' selected' : '') + '>' + escapeHtml(type) + '</option>';
    });
    html += '</select></div>';
    
    html += '<div class="form-group"><label>Actividad Principal <span class="req">*</span></label>';
    html += '<select class="form-control" id="form_main_activity" required>';
    html += '<option value="">...</option>';
    const activities = ['Agroindustria', 'Industria manufacturera', 'Servicios basados en conocimiento', 'Turismo', 'Economía cultural/creativa', 'Otros (especificar)'];
    activities.forEach(act => {
        html += '<option value="' + escapeHtml(act) + '"' + (company.main_activity === act ? ' selected' : '') + '>' + escapeHtml(act) + '</option>';
    });
    html += '</select></div>';
    
    html += '</div>';
    
    // Секция 3: Productos y Servicios
    html += '<div class="user-form-section">';
    html += '<h4 class="section-title">3. Información sobre Productos y Servicios</h4>';
    
    // Определяем, что есть в БД: продукты или услуги
    const allProducts = products.all || (products.main ? [products.main] : []);
    const allServices = services.all || (services.main ? [services.main] : []);
    
    // Объединяем все продукты и услуги в один массив
    const allItems = [];
    allProducts.forEach(item => {
        allItems.push({...item, itemType: 'product'});
    });
    allServices.forEach(item => {
        allItems.push({...item, itemType: 'service'});
    });
    
    // Сортируем: сначала продукты, потом услуги, внутри каждого типа - по ID
    allItems.sort((a, b) => {
        if (a.itemType !== b.itemType) {
            return a.itemType === 'product' ? -1 : 1;
        }
        return (a.id || 0) - (b.id || 0);
    });
    
    const hasItems = allItems.length > 0;
    
    // Certificaciones (общие для всех продуктов/услуг, берем из первого элемента)
    const certificationsValue = hasItems && allItems.length > 0 ? (allItems[0].certifications || '') : '';
    html += '<div class="form-group"><label>Certificaciones</label>';
    html += '<textarea class="form-control" id="form_certifications">' + escapeHtml(certificationsValue) + '</textarea></div>';
    
    // Единый контейнер для всех продуктов и услуг
    html += '<div id="items_list_container" style="margin-top: 20px;">';
    
    // Счетчики для нумерации
    let productCounter = 0;
    let serviceCounter = 0;
    
    allItems.forEach((item, index) => {
        const itemId = item.id || null;
        const isProduct = item.itemType === 'product';
        const itemIndex = isProduct ? productCounter++ : serviceCounter++;
        
        if (isProduct) {
            html += '<div class="product-item-admin" data-product-id="' + (itemId || '') + '" data-product-index="' + itemIndex + '" data-product-type="product" style="position: relative; margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">';
            html += '<div class="item-badge-admin item-badge-product-admin" style="position: absolute; top: 10px; left: 10px; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 700; color: #fff; background: #4CAF50; text-transform: uppercase; z-index: 10;">Producto</div>';
            html += '<h5 style="margin-top: 30px; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 10px;">Producto ' + (itemIndex + 1) + '</h5>';
            
            html += '<div class="form-group"><label>Producto <span class="req">*</span></label>';
            html += '<input type="text" class="form-control product-name" data-index="' + itemIndex + '" value="' + escapeHtml(item.name || '') + '" required></div>';
            
            html += '<div class="form-group"><label>Descripción <span class="req">*</span></label>';
            html += '<input type="text" class="form-control product-description" data-index="' + itemIndex + '" value="' + escapeHtml(item.description || '') + '" required></div>';
            
            html += '<div class="form-group"><label>Exportación Anual (USD)</label>';
            html += '<input type="text" class="form-control product-export" data-index="' + itemIndex + '" value="' + escapeHtml(item.annual_export || '') + '"></div>';
            
            // Фото продукта
            let productPhotos = [];
            if (itemId && files.product_photo && typeof files.product_photo === 'object') {
                if (Array.isArray(files.product_photo)) {
                    // Старый формат - массив
                    if (itemIndex === 0 && files.product_photo.length > 0) {
                        productPhotos = files.product_photo;
                    }
                } else {
                    // Новый формат - объект с ключами product_id
                    if (files.product_photo[itemId]) {
                        productPhotos = Array.isArray(files.product_photo[itemId]) ? files.product_photo[itemId] : [files.product_photo[itemId]];
                    }
                }
            }
            
            if (productPhotos.length > 0) {
                html += '<div class="form-group"><label>Foto del Producto</label>';
                html += displayFiles(productPhotos, itemId, 'product_photo');
                html += '</div>';
            }
            
            // Кнопка удаления продукта
            if (itemId) {
                html += '<button type="button" class="btn btn-danger btn-sm delete-product-btn" data-product-id="' + itemId + '" style="margin-top: 10px;">Eliminar Producto</button>';
            }
            
            html += '</div>';
        } else {
            html += '<div class="service-item-admin" data-service-id="' + (itemId || '') + '" data-service-index="' + itemIndex + '" data-service-type="service" style="position: relative; margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">';
            html += '<div class="item-badge-admin item-badge-service-admin" style="position: absolute; top: 10px; left: 10px; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 700; color: #fff; background: #FF9800; text-transform: uppercase; z-index: 10;">Servicio</div>';
            html += '<h5 style="margin-top: 30px; margin-bottom: 15px; border-bottom: 1px solid #ddd; padding-bottom: 10px;">Servicio ' + (itemIndex + 1) + '</h5>';
            
            // Actividad
            const activityOptions = [
                'Staff augmentation / provisión de perfiles especializados',
                'Implementadores de soluciones',
                'Ciencia de datos',
                'Análisis de datos y scraping',
                'Blockchain',
                'Biotecnología (servicios, prótesis)',
                'Turismo (servicios tecnológicos asociados)',
                'Marketing Digital',
                'Servicios de mantenimiento aeronáutico',
                'IA – servicios de desarrollo (bots de lenguaje natural, soluciones a medida)',
                'e-Government (soluciones para Estado provincial y municipios)',
                'Consultoría de procesos y transformación digital',
                'Diseño mecánico',
                'Diseño 3D',
                'Diseño multimedia',
                'Diseño de hardware',
                'Fintech',
                'Growth Marketing',
                'Economía del Conocimiento – Productos orientados a Salud',
                'Sistemas de facturación'
            ];
            html += '<div class="form-group"><label>Actividad <span class="req">*</span></label>';
            html += '<select class="form-control service-activity" data-index="' + itemIndex + '" required>';
            html += '<option value="">...</option>';
            activityOptions.forEach(option => {
                const selected = (item.activity === option) ? ' selected' : '';
                html += '<option value="' + escapeHtml(option) + '"' + selected + '>' + escapeHtml(option) + '</option>';
            });
            html += '</select></div>';
            
            html += '<div class="form-group"><label>Servicio <span class="req">*</span></label>';
            html += '<input type="text" class="form-control service-name" data-index="' + itemIndex + '" value="' + escapeHtml(item.name || '') + '" required></div>';
            
            html += '<div class="form-group"><label>Descripción <span class="req">*</span></label>';
            html += '<input type="text" class="form-control service-description" data-index="' + itemIndex + '" value="' + escapeHtml(item.description || '') + '" required></div>';
            
            html += '<div class="form-group"><label>Exportación Anual (USD)</label>';
            html += '<input type="text" class="form-control service-export" data-index="' + itemIndex + '" value="' + escapeHtml(item.annual_export || '') + '"></div>';
            
            // Фото услуги
            let servicePhotos = [];
            if (itemId && files.service_photo && typeof files.service_photo === 'object') {
                if (Array.isArray(files.service_photo)) {
                    // Старый формат - массив
                    if (itemIndex === 0 && files.service_photo.length > 0) {
                        servicePhotos = files.service_photo;
                    }
                } else {
                    // Новый формат - объект с ключами product_id
                    if (files.service_photo[itemId]) {
                        servicePhotos = Array.isArray(files.service_photo[itemId]) ? files.service_photo[itemId] : [files.service_photo[itemId]];
                    }
                }
            }
            
            if (servicePhotos.length > 0) {
                html += '<div class="form-group"><label>Foto del Servicio</label>';
                html += displayFiles(servicePhotos, itemId, 'service_photo');
                html += '</div>';
            }
            
            // Кнопка удаления услуги
            if (itemId) {
                html += '<button type="button" class="btn btn-danger btn-sm delete-service-btn" data-service-id="' + itemId + '" style="margin-top: 10px;">Eliminar Servicio</button>';
            }
            
            html += '</div>';
        }
    });
    
    html += '</div>';
    
    // Кнопки для добавления нового продукта или услуги (всегда показываем, даже если список пуст)
    html += '<div style="margin-top: 10px;">';
    html += '<button type="button" class="btn btn-secondary" id="add_product_btn" style="margin-right: 10px;">Agregar Producto</button>';
    html += '<button type="button" class="btn btn-secondary" id="add_service_btn">Agregar Servicio</button>';
    html += '</div>';
    
    // Mercados Actuales (редактируемое поле)
    html += '<div class="form-group"><label>Mercados Actuales (Continente) <span class="req">*</span></label>';
    html += '<select class="form-control" id="form_current_markets" required>';
    html += '<option value="">...</option>';
    const markets = ['América del Norte', 'América del Sur', 'Europa', 'Asia', 'África', 'Oceanía'];
    const currentMarketsValue = companyData.current_markets || '';
    // Если current_markets - массив (старый формат), берем первый элемент
    const currentMarketsStr = Array.isArray(currentMarketsValue) ? (currentMarketsValue[0] || '') : currentMarketsValue;
    markets.forEach(market => {
        html += '<option value="' + escapeHtml(market) + '"' + (currentMarketsStr === market ? ' selected' : '') + '>' + escapeHtml(market) + '</option>';
    });
    html += '</select></div>';
    
    // Mercados de Interés (только чтение)
    if (companyData.target_markets) {
        const targetMarketsDisplay = Array.isArray(companyData.target_markets) 
            ? companyData.target_markets.join(', ') 
            : escapeHtml(companyData.target_markets);
        html += '<div class="form-group"><label>Mercados de Interés (Continente)</label>';
        html += '<div class="readonly-field">' + targetMarketsDisplay + '</div></div>';
    }
    
    html += '</div>';
    
    // Секция 4: Competitividad (редактируемая)
    html += '<div class="user-form-section">';
    html += '<h4 class="section-title">4. Competitividad y Diferenciación</h4>';
    
    // Factores de Diferenciación
    const diffFactors = companyData.differentiation_factors || [];
    const otherDiff = (companyData.competitiveness && companyData.competitiveness.other_differentiation) ? companyData.competitiveness.other_differentiation : '';
    const hasOtherDiff = diffFactors.includes('Otros');
    
    html += '<div class="form-group"><label>Factores de Diferenciación</label>';
    const diffOptions = ['Calidad', 'Innovación', 'Origen territorial', 'Trazabilidad', 'Precio competitivo', 'Otros'];
    diffOptions.forEach(factor => {
        const checked = diffFactors.includes(factor) ? ' checked' : '';
        html += '<div class="checkbox-group">';
        html += '<label><input type="checkbox" class="diff-factor" value="' + escapeHtml(factor) + '"' + checked + '> ' + escapeHtml(factor) + '</label>';
        html += '</div>';
    });
    html += '<div class="form-group" style="margin-top: 10px;">';
    html += '<input type="text" class="form-control" id="form_other_differentiation" placeholder="Especificar otros factores" value="' + escapeHtml(otherDiff) + '"' + (hasOtherDiff ? '' : ' disabled') + '>';
    html += '</div></div>';
    
    // Historia de la Empresa
    const companyHistory = (companyData.competitiveness && companyData.competitiveness.company_history) ? companyData.competitiveness.company_history : '';
    html += '<div class="form-group"><label>Historia de la Empresa y del Producto <span class="req">*</span></label>';
    html += '<textarea class="form-control" id="form_company_history" rows="4" required>' + escapeHtml(companyHistory) + '</textarea></div>';
    
    // Premios
    const awards = (companyData.competitiveness && companyData.competitiveness.awards) ? companyData.competitiveness.awards : '';
    const awardsDetail = (companyData.competitiveness && companyData.competitiveness.awards_detail) ? companyData.competitiveness.awards_detail : '';
    html += '<div class="form-group"><label>Premios <span class="req">*</span></label>';
    html += '<div class="radio-group">';
    html += '<label><input type="radio" name="form_awards" value="si"' + (awards === 'si' ? ' checked' : '') + '> Sí</label>';
    html += '<label><input type="radio" name="form_awards" value="no"' + (awards === 'no' ? ' checked' : '') + '> No</label>';
    html += '</div>';
    html += '<input type="text" class="form-control" id="form_awards_detail" placeholder="Detalles" value="' + escapeHtml(awardsDetail) + '" style="margin-top: 10px;">';
    html += '</div>';
    
    // Ferias
    const fairs = (companyData.competitiveness && companyData.competitiveness.fairs) ? companyData.competitiveness.fairs : '';
    html += '<div class="form-group"><label>Ferias <span class="req">*</span></label>';
    html += '<div class="radio-group">';
    html += '<label><input type="radio" name="form_fairs" value="si"' + (fairs === 'si' ? ' checked' : '') + '> Sí</label>';
    html += '<label><input type="radio" name="form_fairs" value="no"' + (fairs === 'no' ? ' checked' : '') + '> No</label>';
    html += '</div></div>';
    
    // Rondas
    const rounds = (companyData.competitiveness && companyData.competitiveness.rounds) ? companyData.competitiveness.rounds : '';
    html += '<div class="form-group"><label>Rondas <span class="req">*</span></label>';
    html += '<div class="radio-group">';
    html += '<label><input type="radio" name="form_rounds" value="si"' + (rounds === 'si' ? ' checked' : '') + '> Sí</label>';
    html += '<label><input type="radio" name="form_rounds" value="no"' + (rounds === 'no' ? ' checked' : '') + '> No</label>';
    html += '</div></div>';
    
    // Experiencia Exportadora
    const exportExperience = (companyData.competitiveness && companyData.competitiveness.export_experience) ? companyData.competitiveness.export_experience : '';
    html += '<div class="form-group"><label>Experiencia Exportadora previa <span class="req">*</span></label>';
    html += '<select class="form-control" id="form_export_experience" required>';
    html += '<option value="">...</option>';
    const expOptions = ['Sí, ya exportamos regularmente', 'Hemos exportado ocasionalmente', 'Nunca exportamos'];
    expOptions.forEach(opt => {
        html += '<option value="' + escapeHtml(opt) + '"' + (exportExperience === opt ? ' selected' : '') + '>' + escapeHtml(opt) + '</option>';
    });
    html += '</select></div>';
    
    // Referencias comerciales
    const commercialRefs = (companyData.competitiveness && companyData.competitiveness.commercial_references) ? companyData.competitiveness.commercial_references : '';
    html += '<div class="form-group"><label>Referencias comerciales <span class="req">*</span></label>';
    html += '<textarea class="form-control" id="form_commercial_references" rows="4" required>' + escapeHtml(commercialRefs) + '</textarea></div>';
    
    html += '</div>';
    
    // Секция 5: Visual (только просмотр)
    html += '<div class="user-form-section">';
    html += '<h4 class="section-title">5. Información Visual y Promocional</h4>';
    
    if (files.logo && files.logo.length > 0) {
        html += '<div class="form-group"><label>Logo de la Empresa</label>';
        html += displayFiles(files.logo);
        html += '</div>';
    }
    
    if (files.process_photo && files.process_photo.length > 0) {
        html += '<div class="form-group"><label>Fotos de los Procesos/Servicios</label>';
        html += displayFiles(files.process_photo);
        html += '</div>';
    }
    
    if (files.digital_catalog && files.digital_catalog.length > 0) {
        html += '<div class="form-group"><label>Catálogo Digital</label>';
        html += displayFiles(files.digital_catalog);
        html += '</div>';
    }
    
    if (files.institutional_video && files.institutional_video.length > 0) {
        html += '<div class="form-group"><label>Video Institucional</label>';
        html += displayFiles(files.institutional_video);
        html += '</div>';
    }
    
    html += '</div>';
    
    // Секция 6: Logística (редактируемая)
    html += '<div class="user-form-section">';
    html += '<h4 class="section-title">6. Logística y Distribución</h4>';
    
    const logistics = companyData.logistics || {};
    const exportCapacity = logistics.export_capacity || '';
    const estimatedTerm = logistics.estimated_term || '';
    const logisticsInfra = logistics.logistics_infrastructure || '';
    const portsAirports = logistics.ports_airports || '';
    
    html += '<div class="form-group"><label>Capacidad de Exportación Inmediata <span class="req">*</span></label>';
    html += '<div class="radio-group">';
    html += '<label><input type="radio" name="form_export_capacity" value="si"' + (exportCapacity === 'si' ? ' checked' : '') + '> Sí</label>';
    html += '<label><input type="radio" name="form_export_capacity" value="no"' + (exportCapacity === 'no' ? ' checked' : '') + '> No</label>';
    html += '</div></div>';
    
    html += '<div class="form-group"><label>Plazo estimado <span class="req">*</span></label>';
    html += '<input type="text" class="form-control" id="form_estimated_term" placeholder="meses" value="' + escapeHtml(estimatedTerm) + '" required></div>';
    
    html += '<div class="form-group"><label>Infraestructura Logística Disponible <span class="req">*</span></label>';
    html += '<input type="text" class="form-control" id="form_logistics_infrastructure" placeholder="ejemplo: frigoríficos, transporte propio, alianzas logísticas, etc." value="' + escapeHtml(logisticsInfra) + '" required></div>';
    
    html += '<div class="form-group"><label>Puertos/Aeropuertos de Salida habituales o posibles <span class="req">*</span></label>';
    html += '<textarea class="form-control" id="form_ports_airports" rows="4" required>' + escapeHtml(portsAirports) + '</textarea></div>';
    
    html += '</div>';
    
    // Секция 7: Necesidades (редактируемая)
    html += '<div class="user-form-section">';
    html += '<h4 class="section-title">7. Necesidades y Expectativas</h4>';
    
    const needs = companyData.needs || [];
    const otherNeeds = (companyData.expectations && companyData.expectations.other_needs) ? companyData.expectations.other_needs : '';
    const hasOtherNeeds = needs.includes('Otros');
    const interestParticipate = (companyData.expectations && companyData.expectations.interest_participate) ? companyData.expectations.interest_participate : '';
    const trainingAvailability = (companyData.expectations && companyData.expectations.training_availability) ? companyData.expectations.training_availability : '';
    
    html += '<div class="form-group"><label>Principales Necesidades para mejorar capacidad exportadora <span class="req">*</span></label>';
    const needsOptions = ['Capacitación', 'Acceso a ferias', 'Certificaciones', 'Financiamiento', 'Socios comerciales', 'Otros'];
    needsOptions.forEach(need => {
        const checked = needs.includes(need) ? ' checked' : '';
        html += '<div class="checkbox-group">';
        html += '<label><input type="checkbox" class="need-option" value="' + escapeHtml(need) + '"' + checked + '> ' + escapeHtml(need) + '</label>';
        html += '</div>';
    });
    html += '<div class="form-group" style="margin-top: 10px;">';
    html += '<input type="text" class="form-control" id="form_other_needs" placeholder="Especificar otras necesidades" value="' + escapeHtml(otherNeeds) + '"' + (hasOtherNeeds ? '' : ' disabled') + '>';
    html += '</div></div>';
    
    html += '<div class="form-group"><label>Interés en Participar de Misiones Comerciales/Ferias Internacionales <span class="req">*</span></label>';
    html += '<div class="radio-group">';
    html += '<label><input type="radio" name="form_interest_participate" value="si"' + (interestParticipate === 'si' ? ' checked' : '') + '> Sí</label>';
    html += '<label><input type="radio" name="form_interest_participate" value="no"' + (interestParticipate === 'no' ? ' checked' : '') + '> No</label>';
    html += '</div></div>';
    
    html += '<div class="form-group"><label>Disponibilidad para Capacitaciones y Asistencia Técnica <span class="req">*</span></label>';
    html += '<div class="radio-group">';
    html += '<label><input type="radio" name="form_training_availability" value="si"' + (trainingAvailability === 'si' ? ' checked' : '') + '> Sí</label>';
    html += '<label><input type="radio" name="form_training_availability" value="no"' + (trainingAvailability === 'no' ? ' checked' : '') + '> No</label>';
    html += '</div></div>';
    
    html += '</div>';
    
    // Секция 8: Validación (редактируемая)
    html += '<div class="user-form-section">';
    html += '<h4 class="section-title">8. Validación y Consentimiento</h4>';
    
    const consents = companyData.consents || {};
    const authPublish = consents.authorization_publish || '';
    const authPublication = consents.authorization_publication || '';
    const acceptContact = consents.accept_contact || '';
    
    html += '<div class="form-group"><label>Autorización para Difundir la Información Cargada en la Plataforma Provincial <span class="req">*</span></label>';
    html += '<div class="radio-group">';
    html += '<label><input type="radio" name="form_authorization_publish" value="si"' + (authPublish === 'si' ? ' checked' : '') + '> Sí</label>';
    html += '<label><input type="radio" name="form_authorization_publish" value="no"' + (authPublish === 'no' ? ' checked' : '') + '> No</label>';
    html += '</div></div>';
    
    html += '<div class="form-group"><label>Autorizo la Publicación de mi Información para Promoción Exportadora <span class="req">*</span></label>';
    html += '<div class="radio-group">';
    html += '<label><input type="radio" name="form_authorization_publication" value="si"' + (authPublication === 'si' ? ' checked' : '') + '> Sí</label>';
    html += '<label><input type="radio" name="form_authorization_publication" value="no"' + (authPublication === 'no' ? ' checked' : '') + '> No</label>';
    html += '</div></div>';
    
    html += '<div class="form-group"><label>Acepto ser Contactado por Organismos de Promoción y Compradores Internacionales <span class="req">*</span></label>';
    html += '<div class="radio-group">';
    html += '<label><input type="radio" name="form_accept_contact" value="si"' + (acceptContact === 'si' ? ' checked' : '') + '> Sí</label>';
    html += '<label><input type="radio" name="form_accept_contact" value="no"' + (acceptContact === 'no' ? ' checked' : '') + '> No</label>';
    html += '</div></div>';
    
    html += '</div>';
    
    // Кнопка Guardar
    html += '<div class="form-actions">';
    html += '<button type="button" class="btn btn-primary" onclick="saveUserFullData(' + userId + ')">Guardar</button>';
    
    // Кнопка подтверждения модерации (только для админов и если статус pending)
    if (!isApproved) {
        html += '<button type="button" class="btn btn-success" onclick="approveModeration(' + userId + ')" style="margin-left: 10px;">Confirmar datos</button>';
    }
    
    html += '<div id="save_message" style="margin-top: 10px;"></div>';
    html += '</div>';
    
    html += '</form>';
    
    return html;
}

// Функция для сохранения только базовых данных пользователя (когда нет данных компании)
function saveUserBasicData(userId) {
    const errors = [];
    
    // Валидация базовых полей
    const emailValue = document.getElementById('form_user_email')?.value.trim() || '';
    if (!emailValue) {
        errors.push('Correo electrónico');
    } else {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(emailValue)) {
            errors.push('Correo electrónico (formato inválido)');
        }
    }
    
    const phoneValue = document.getElementById('form_user_phone')?.value.trim() || '';
    if (!phoneValue) {
        errors.push('Teléfono');
    }
    
    const isAdminValue = document.getElementById('form_user_is_admin')?.value || '0';
    
    if (errors.length > 0) {
        document.getElementById('save_message').innerHTML = '<div class="alert alert-danger">Por favor, complete los campos obligatorios: ' + errors.join(', ') + '</div>';
        return;
    }
    
    const formData = {
        user_id: userId,
        user_email: emailValue,
        user_phone: phoneValue,
        user_is_admin: isAdminValue
    };
    
    document.getElementById('save_message').innerHTML = '<div class="alert alert-info">Guardando...</div>';
    
    const basePathValue = window.basePath || basePath || '';
    fetch(basePathValue + 'includes/users_update_basic_data_js.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok === 1) {
            document.getElementById('save_message').innerHTML = '<div class="alert alert-success">Datos guardados correctamente</div>';
            // Перезагружаем данные после успешного сохранения
            setTimeout(() => {
                loadUserFullData(userId);
            }, 1000);
        } else {
            document.getElementById('save_message').innerHTML = '<div class="alert alert-danger">Error: ' + (data.err || 'Error desconocido') + '</div>';
        }
    })
    .catch(error => {
        console.error('Error saving:', error);
        document.getElementById('save_message').innerHTML = '<div class="alert alert-danger">Error de conexión</div>';
    });
}

function displayFiles(files, productId = null, fileType = null) {
    if (!files || files.length === 0) return '';
    
    let html = '<div class="files-preview">';
    files.forEach(file => {
        const isImage = file.mime_type && file.mime_type.startsWith('image/');
        const isVideo = file.mime_type && file.mime_type.startsWith('video/');
        const isPDF = file.mime_type === 'application/pdf';
        
        html += '<div class="file-item-preview" data-file-id="' + (file.id || '') + '">';
        
        if (isImage) {
            html += '<img src="' + escapeHtml(file.url) + '" alt="' + escapeHtml(file.name) + '" style="max-width: 200px; max-height: 150px; margin: 5px;">';
        } else if (isVideo) {
            html += '<video src="' + escapeHtml(file.url) + '" controls style="max-width: 300px; max-height: 200px; margin: 5px;"></video>';
        } else {
            html += '<span>📄 ' + escapeHtml(file.name) + '</span>';
        }
        
        html += '<br><a href="' + escapeHtml(file.url) + '" target="_blank" download>Descargar: ' + escapeHtml(file.name) + '</a>';
        
        // Кнопка удаления файла
        if (file.id) {
            html += '<button type="button" class="btn btn-sm btn-danger delete-file-btn" data-file-id="' + file.id + '" style="margin-left: 10px; margin-top: 5px;">Eliminar</button>';
        }
        
        html += '</div>';
    });
    html += '</div>';
    return html;
}

function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Сохранение данных
function saveUserFullData(userId) {
    const errors = validateForm();
    
    if (errors.length > 0) {
        document.getElementById('save_message').innerHTML = '<div class="alert alert-danger">Por favor, complete los campos obligatorios:<ul>' + 
            errors.map(e => '<li>' + e + '</li>').join('') + '</ul></div>';
        return;
    }
    
    // Сбор данных с использованием умной логики: измененные поля + исходные значения для неизмененных
    const formData = {
        user_id: userId,
        user_email: getFieldValue('form_user_email', originalFormData.user_email),
        user_phone: getFieldValue('form_user_phone', originalFormData.user_phone),
        user_is_admin: isFieldChanged('form_user_is_admin') 
            ? (document.getElementById('form_user_is_admin')?.value || '0') 
            : (originalFormData.user_is_admin || '0'),
        name: getFieldValue('form_name', originalFormData.name),
        tax_id: getFieldValue('form_tax_id', originalFormData.tax_id),
        legal_name: getFieldValue('form_legal_name', originalFormData.legal_name),
        start_date: getFieldValue('form_start_date', originalFormData.start_date),
        website: getFieldValue('form_website', originalFormData.website),
        organization_type: isFieldChanged('form_organization_type') 
            ? (document.getElementById('form_organization_type')?.value || '') 
            : (originalFormData.organization_type || ''),
        main_activity: isFieldChanged('form_main_activity') 
            ? (document.getElementById('form_main_activity')?.value || '') 
            : (originalFormData.main_activity || ''),
        current_markets: isFieldChanged('form_current_markets') 
            ? (document.getElementById('form_current_markets')?.value || '') 
            : (originalFormData.current_markets || ''),
        certifications: getFieldValue('form_certifications', ''),
        // Продукты (массив)
        products: collectProductsData(),
        // Секция 4: Competitividad
        differentiation_factors: collectCheckboxValues('.diff-factor'),
        other_differentiation: getFieldValue('form_other_differentiation', ''),
        company_history: getFieldValue('form_company_history', ''),
        awards: getRadioValue('form_awards'),
        awards_detail: getFieldValue('form_awards_detail', ''),
        fairs: getRadioValue('form_fairs'),
        rounds: getRadioValue('form_rounds'),
        export_experience: getFieldValue('form_export_experience', ''),
        commercial_references: getFieldValue('form_commercial_references', ''),
        // Секция 6: Logística
        export_capacity: getRadioValue('form_export_capacity'),
        estimated_term: getFieldValue('form_estimated_term', ''),
        logistics_infrastructure: getFieldValue('form_logistics_infrastructure', ''),
        ports_airports: getFieldValue('form_ports_airports', ''),
        // Секция 7: Necesidades
        needs: collectCheckboxValues('.need-option'),
        other_needs: getFieldValue('form_other_needs', ''),
        interest_participate: getRadioValue('form_interest_participate'),
        training_availability: getRadioValue('form_training_availability'),
        // Секция 8: Consentimientos
        authorization_publish: getRadioValue('form_authorization_publish'),
        authorization_publication: getRadioValue('form_authorization_publication'),
        accept_contact: getRadioValue('form_accept_contact')
    };
    
    // Отправка
    document.getElementById('save_message').innerHTML = '<div class="alert alert-info">Guardando...</div>';
    
    const basePathValue = window.basePath || basePath || '';
    fetch(basePathValue + 'includes/users_update_full_data_js.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok === 1) {
            document.getElementById('save_message').innerHTML = '<div class="alert alert-success">' + (data.res || 'Datos guardados correctamente') + '</div>';
            // Сбрасываем отслеживание изменений после успешного сохранения
            changedFields = {};
            setTimeout(() => {
                loadUserFullData(userId); // Перезагружаем данные
            }, 1000);
        } else {
            document.getElementById('save_message').innerHTML = '<div class="alert alert-danger">Error: ' + (data.err || 'Error desconocido') + '</div>';
        }
    })
    .catch(error => {
        console.error('Error saving:', error);
        document.getElementById('save_message').innerHTML = '<div class="alert alert-danger">Error de conexión</div>';
    });
}

function validateForm() {
    const errors = [];
    
    // Умная валидация: проверяем только измененные поля или обязательные поля, которые пустые в исходных данных
    
    // Email
    const emailValue = getFieldValue('form_user_email', originalFormData.user_email);
    if (isFieldChanged('form_user_email')) {
        if (!emailValue) {
            errors.push('Correo electrónico');
        } else {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(emailValue)) {
                errors.push('Correo electrónico (formato inválido)');
            }
        }
    } else if (!originalFormData.user_email) {
        errors.push('Correo electrónico');
    }
    
    // Teléfono
    const phoneValue = getFieldValue('form_user_phone', originalFormData.user_phone);
    if (isFieldChanged('form_user_phone')) {
        if (!phoneValue) errors.push('Teléfono');
    } else if (!originalFormData.user_phone) {
        errors.push('Teléfono');
    }
    
    // Es Administrador
    const isAdminField = document.getElementById('form_user_is_admin');
    const isAdminValue = isFieldChanged('form_user_is_admin') ? (isAdminField ? isAdminField.value : '0') : originalFormData.user_is_admin;
    if (isFieldChanged('form_user_is_admin')) {
        if (isAdminValue === '' || isAdminValue === null) errors.push('Es Administrador');
    } else if (originalFormData.user_is_admin === '' || originalFormData.user_is_admin === null) {
        errors.push('Es Administrador');
    }
    
    // Nombre de la Empresa
    const nameValue = getFieldValue('form_name', originalFormData.name);
    if (isFieldChanged('form_name')) {
        if (!nameValue) errors.push('Nombre de la Empresa');
    } else if (!originalFormData.name) {
        errors.push('Nombre de la Empresa');
    }
    
    // CUIT
    const taxIdValue = getFieldValue('form_tax_id', originalFormData.tax_id);
    if (isFieldChanged('form_tax_id')) {
        if (!taxIdValue) errors.push('CUIT');
    } else if (!originalFormData.tax_id) {
        errors.push('CUIT');
    }
    
    // Razón social
    const legalNameValue = getFieldValue('form_legal_name', originalFormData.legal_name);
    if (isFieldChanged('form_legal_name')) {
        if (!legalNameValue) errors.push('Razón social');
    } else if (!originalFormData.legal_name) {
        errors.push('Razón social');
    }
    
    // Fecha de Inicio
    const startDateValue = getFieldValue('form_start_date', originalFormData.start_date);
    if (isFieldChanged('form_start_date')) {
        if (!startDateValue) {
            errors.push('Fecha de Inicio');
        } else {
            const datePattern = /^\d{2}\/\d{2}\/\d{4}$/;
            if (!datePattern.test(startDateValue)) {
                errors.push('Fecha de Inicio (formato: dd/mm/yyyy)');
            }
        }
    } else if (!originalFormData.start_date) {
        errors.push('Fecha de Inicio');
    } else {
        const datePattern = /^\d{2}\/\d{2}\/\d{4}$/;
        if (originalFormData.start_date && !datePattern.test(originalFormData.start_date)) {
            errors.push('Fecha de Inicio (formato: dd/mm/yyyy)');
        }
    }
    
    // Tipo de Organización
    const orgTypeField = document.getElementById('form_organization_type');
    const orgTypeValue = isFieldChanged('form_organization_type') ? (orgTypeField ? orgTypeField.value : '') : originalFormData.organization_type;
    if (isFieldChanged('form_organization_type')) {
        if (!orgTypeValue) errors.push('Tipo de Organización');
    } else if (!originalFormData.organization_type) {
        errors.push('Tipo de Organización');
    }
    
    // Actividad Principal
    const mainActivityField = document.getElementById('form_main_activity');
    const mainActivityValue = isFieldChanged('form_main_activity') ? (mainActivityField ? mainActivityField.value : '') : originalFormData.main_activity;
    if (isFieldChanged('form_main_activity')) {
        if (!mainActivityValue) errors.push('Actividad Principal');
    } else if (!originalFormData.main_activity) {
        errors.push('Actividad Principal');
    }
    
    // Основной продукт
    const mainProduct = originalFormData.main_product || {};
    const mainProductNameValue = getFieldValue('form_main_product_name', mainProduct.name);
    if (isFieldChanged('form_main_product_name')) {
        if (!mainProductNameValue) errors.push('Producto principal');
    } else if (!mainProduct.name) {
        errors.push('Producto principal');
    }
    
    const mainProductDescValue = getFieldValue('form_main_product_description', mainProduct.description);
    if (isFieldChanged('form_main_product_description')) {
        if (!mainProductDescValue) errors.push('Descripción del producto');
    } else if (!mainProduct.description) {
        errors.push('Descripción del producto');
    }
    
    // Mercados Actuales (обязательное поле)
    const currentMarketsField = document.getElementById('form_current_markets');
    const currentMarketsValue = isFieldChanged('form_current_markets') 
        ? (currentMarketsField ? currentMarketsField.value : '') 
        : (originalFormData.current_markets || '');
    if (isFieldChanged('form_current_markets')) {
        if (!currentMarketsValue) errors.push('Mercados Actuales (Continente)');
    } else if (!originalFormData.current_markets) {
        errors.push('Mercados Actuales (Continente)');
    }
    
    return errors;
}

// Функция подтверждения модерации
function approveModeration(userId) {
    if (!confirm('¿Está seguro de que desea confirmar los datos de este usuario?')) {
        return;
    }
    
    const basePathValue = window.basePath || basePath || '';
    const messageEl = document.getElementById('save_message');
    
    if (messageEl) {
        messageEl.innerHTML = '<div class="alert alert-info">Confirmando datos...</div>';
    }
    
    fetch(basePathValue + 'includes/users_approve_moderation_js.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.ok === 1) {
            if (messageEl) {
                messageEl.innerHTML = '<div class="alert alert-success">Datos confirmados correctamente. Recargando página...</div>';
            }
            // Перезагружаем страницу после успешного подтверждения
            setTimeout(() => {
                location.reload();
            }, 500);
        } else {
            if (messageEl) {
                messageEl.innerHTML = '<div class="alert alert-danger">Error: ' + (data.err || 'Error desconocido') + '</div>';
            }
        }
    })
    .catch(error => {
        console.error('Error approving moderation:', error);
        if (messageEl) {
            messageEl.innerHTML = '<div class="alert alert-danger">Error de conexión</div>';
        }
    });
}

// Вспомогательные функции для сбора данных формы

function collectProductsData() {
    const products = [];
    const services = [];
    
    // Собираем продукты
    const productItems = document.querySelectorAll('.product-item-admin');
    productItems.forEach((item, index) => {
        const productId = item.getAttribute('data-product-id');
        const nameInput = item.querySelector('.product-name[data-index="' + index + '"]');
        const descInput = item.querySelector('.product-description[data-index="' + index + '"]');
        const exportInput = item.querySelector('.product-export[data-index="' + index + '"]');
        
        if (nameInput && descInput) {
            products.push({
                id: productId && productId !== 'null' ? parseInt(productId) : null,
                type: 'product',
                name: nameInput.value.trim() || '',
                description: descInput.value.trim() || '',
                annual_export: exportInput ? exportInput.value.trim() : ''
            });
        }
    });
    
    // Собираем услуги
    const serviceItems = document.querySelectorAll('.service-item-admin');
    serviceItems.forEach((item, index) => {
        const serviceId = item.getAttribute('data-service-id');
        const activitySelect = item.querySelector('.service-activity[data-index="' + index + '"]');
        const nameInput = item.querySelector('.service-name[data-index="' + index + '"]');
        const descInput = item.querySelector('.service-description[data-index="' + index + '"]');
        const exportInput = item.querySelector('.service-export[data-index="' + index + '"]');
        
        if (nameInput && descInput && activitySelect) {
            services.push({
                id: serviceId && serviceId !== 'null' ? parseInt(serviceId) : null,
                type: 'service',
                activity: activitySelect.value.trim() || '',
                name: nameInput.value.trim() || '',
                description: descInput.value.trim() || '',
                annual_export: exportInput ? exportInput.value.trim() : ''
            });
        }
    });
    
    // Объединяем продукты и услуги в один массив для отправки
    return products.concat(services);
}

function collectCheckboxValues(selector) {
    const checkboxes = document.querySelectorAll(selector + ':checked');
    const values = [];
    checkboxes.forEach(cb => {
        if (cb.value) {
            values.push(cb.value);
        }
    });
    return values;
}

function getRadioValue(name) {
    const radio = document.querySelector('input[name="' + name + '"]:checked');
    return radio ? radio.value : '';
}

