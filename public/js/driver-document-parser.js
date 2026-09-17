(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    else root.DriverDocumentParser = api;
})(typeof window !== 'undefined' ? window : globalThis, function () {
    'use strict';
    const states = 'AN AP AR AS BR CG CH DD DL DN GA GJ HP HR JH JK KA KL LA LD MH ML MN MP MZ NL OD OR PB PY RJ SK TN TR TS TG UK UP UT WB'.split(' ');
    const indianStates = ['Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal', 'Delhi', 'Chandigarh', 'Puducherry', 'Jammu and Kashmir', 'Ladakh'];
    const commonCities = 'Ahmedabad Surat Vadodara Rajkot Gandhinagar Jamnagar Bhavnagar Junagadh Mumbai Pune Nagpur Nashik Thane Delhi Jaipur Jodhpur Udaipur Kota Bengaluru Mysuru Chennai Coimbatore Hyderabad Warangal Kolkata Lucknow Kanpur Agra Varanasi Bhopal Indore Patna Ranchi Chandigarh Ludhiana Amritsar Kochi Thiruvananthapuram'.split(' ');
    const aadhaarAddressLabelPattern = '(?:ADDRESS|ADDR(?:ESS)?|C\\/O|S\\/O|D\\/O|W\\/O|CARE OF|SON OF|DAUGHTER OF|WIFE OF|पता|पत्ता|સરનામું|સરનામુ|સરનામા|ঠিকানা|ਪਤਾ|முகவரி|చిరునామా|ವಿಳಾಸ|വിലാസം|پتہ)';
    const unique = values => [...new Set(values.filter(Boolean))];
    const digits = value => String(value).replace(/[\u0966-\u096f\u0ae6-\u0aef]/g, ch => String(ch.charCodeAt(0) - (ch >= '\u0ae6' ? 0xAE6 : 0x966)));

    function date(value) {
        const match = digits(value).match(/\b(?:(\d{4})[-/.](\d{1,2})[-/.](\d{1,2})|(\d{1,2})[-/.\s]+(\d{1,2}|JAN(?:UARY)?|FEB(?:RUARY)?|MAR(?:CH)?|APR(?:IL)?|MAY|JUN(?:E)?|JUL(?:Y)?|AUG(?:UST)?|SEP(?:TEMBER)?|OCT(?:OBER)?|NOV(?:EMBER)?|DEC(?:EMBER)?)[-/.\s]+(\d{4}))\b/i);
        if (!match) return '';
        const year = Number(match[1] || match[6]);
        const monthText = match[2] || match[5];
        const month = /^\d+$/.test(monthText) ? Number(monthText) : 'JAN FEB MAR APR MAY JUN JUL AUG SEP OCT NOV DEC'.split(' ').indexOf(monthText.slice(0, 3).toUpperCase()) + 1;
        const day = Number(match[3] || match[4]);
        const parsed = new Date(year, month - 1, day);
        if (year < 1900 || year > 2199 || parsed.getFullYear() !== year || parsed.getMonth() !== month - 1 || parsed.getDate() !== day) return '';
        return `${String(day).padStart(2, '0')}/${String(month).padStart(2, '0')}/${year}`;
    }

    function dates(value) {
        return [...digits(value).matchAll(/\b(?:\d{4}[-/.]\d{1,2}[-/.]\d{1,2}|\d{1,2}[-/.\s]+(?:\d{1,2}|[A-Z]{3,9})[-/.\s]+\d{4})\b/gi)].map(match => date(match[0])).filter(Boolean);
    }

    function cleanName(value) {
        let candidate = value.replace(/^[\s:|.\-]+/, '').split(/\s+(?:S\/O|D\/O|W\/O|C\/O|DOB|DATE OF|SON OF|DAUGHTER OF|ADDRESS|VALID|LICEN[CS]E)\b/i)[0].trim();
        candidate = candidate.replace(/^(?:[a-z]\s+)+(?=[A-Z][a-z])/, '');
        if (candidate.length < 3 || candidate.length > 90 || !/^[A-Za-z][A-Za-z .'-]+$/.test(candidate)) return '';
        if (/\b(?:INDIA|GOVERNMENT|DEPARTMENT|TRANSPORT|LICEN[CS]E|AUTHORITY|FATHER|MOTHER|ADDRESS|BIRTH|VALIDITY|MALE|FEMALE|SIGNATURE|IDENTIFICATION|AADHAAR|UNIQUE|VID)\b/i.test(candidate)) return '';
        return candidate.replace(/\s+/g, ' ');
    }

    function aadhaarNameLine(line) {
        if (/\d{4}|DOB|DATE OF BIRTH|YEAR OF BIRTH|MALE|FEMALE|GOVERNMENT|INDIA|UNIQUE|IDENTIFICATION|AUTHORITY|AADHAAR|VID|ADDRESS|FATHER|MOTHER|S\/O|D\/O|W\/O|C\/O/i.test(line)) return '';
        return cleanName(line);
    }

    function hasAadhaarAddressLabel(line) {
        return /(?:ADDRESS|ADDR(?:ESS)?|C\/O|S\/O|D\/O|W\/O|CARE OF|SON OF|DAUGHTER OF|WIFE OF|पता|पत्ता|સરનામું|સરનામુ|સરનામા|ঠিকানা|ਪਤਾ|முகவரி|చిరునామా|ವಿಳಾಸ|വിലാസം|پتہ)/i.test(String(line || ''));
    }

    function cleanAddressLine(line) {
        const labelRegex = new RegExp('^.*?' + aadhaarAddressLabelPattern + '\\s*[:.\\-]?\\s*', 'iu');
        const value = String(line || '')
            .replace(labelRegex, '')
            .replace(/\b(?:MOBILE|PHONE|VID)\b.*$/i, '')
            .replace(/[^\p{L}\p{M}0-9,./#()\- ]+/gu, ' ')
            .replace(/\s+/g, ' ')
            .trim();
        if (!value || value.length < 3) return '';
        if (/(?<!\d)[2-9]\d{3}[ -]?\d{4}[ -]?\d{4}(?!\d)/.test(value)) return '';
        if (/\b(?:GOVERNMENT|INDIA|UNIQUE|IDENTIFICATION|AUTHORITY|AADHAAR|DOB|DATE OF BIRTH|YEAR OF BIRTH|MALE|FEMALE|SIGNATURE|ENROLMENT)\b/i.test(value)) return '';
        return value;
    }

    function isReadableAddress(address) {
        const value = String(address || '').replace(/\s+/g, ' ').trim();
        if (!value) return false;
        const words = value.match(/\p{L}[\p{L}\p{M}0-9'-]*/gu) || [];
        if (words.length < 3) return /\b[1-9]\d{5}\b/.test(value);
        const singleLetterWords = words.filter(word => /^\p{L}\p{M}*$/u.test(word)).length;
        const noVowelWords = words.filter(word => /^[A-Za-z]{4,}$/.test(word) && !/[AEIOUaeiou]/.test(word)).length;
        const mixedCaseJunk = words.filter(word => word.length >= 4 && /[a-z][A-Z]|[A-Z]{2,}[a-z]/.test(word)).length;
        const uppercaseRuns = value.match(/(?:\b[A-Z]{2,}\b\s*){3,}/g) || [];
        const hasBrokenUppercaseRun = uppercaseRuns.some(run =>
            run.split(/\s+/).some(word => /^[A-Z]{4,}$/.test(word) && !/[AEIOU]/.test(word))
        );
        if (singleLetterWords >= 4) return false;
        if (noVowelWords >= 4) return false;
        // OCR errors such as "STPSAT FAT THTT" or "fazT" must never be
        // written into an address field.  A user can re-scan a clearer back
        // side or enter the address manually.
        if (mixedCaseJunk >= 1 || hasBrokenUppercaseRun) return false;
        return true;
    }

    function extractAadhaarAddresses(lines) {
        const addresses = [];
        lines.forEach((line, index) => {
            if (!hasAadhaarAddressLabel(line)) return;
            const parts = [];
            const sameLine = cleanAddressLine(line);
            if (sameLine) parts.push(sameLine);
            for (let next = index + 1; next < Math.min(lines.length, index + 7); next++) {
                const raw = lines[next];
                if (/(?<!\d)[2-9]\d{3}[ -]?\d{4}[ -]?\d{4}(?!\d)|\b(?:VID|AADHAAR|GOVERNMENT|UNIQUE|IDENTIFICATION|AUTHORITY|DOB|DATE OF BIRTH|YEAR OF BIRTH|MALE|FEMALE)\b/i.test(raw)) break;
                const cleaned = cleanAddressLine(raw);
                if (!cleaned) break;
                parts.push(cleaned);
            }
            const address = parts.join(', ').replace(/\s*,\s*/g, ', ').replace(/(?:,\s*){2,}/g, ', ').trim();
            if (address.length >= 8 && address.length <= 300 && isReadableAddress(address)) addresses.push(address);
        });
        return addresses;
    }

    function splitAddress(address) {
        const value = String(address || '').replace(/\s+/g, ' ').trim();
        if (!value) return ['', ''];
        const parts = value.split(/\s*,\s*/).filter(Boolean);
        if (parts.length >= 2) {
            const mid = Math.ceil(parts.length / 2);
            return [parts.slice(0, mid).join(', '), parts.slice(mid).join(', ')];
        }
        if (value.length <= 70) return [value, ''];
        const cut = value.lastIndexOf(' ', 70);
        const index = cut > 25 ? cut : 70;
        return [value.slice(0, index).trim(), value.slice(index).trim()];
    }

    function stateFromPincode(pin) {
        const prefix = String(pin || '').slice(0, 2);
        const statesByPrefix = {
            11: 'Delhi',
            12: 'Haryana', 13: 'Haryana',
            14: 'Punjab', 15: 'Punjab', 16: 'Punjab',
            17: 'Himachal Pradesh',
            18: 'Jammu and Kashmir', 19: 'Jammu and Kashmir',
            20: 'Uttar Pradesh', 21: 'Uttar Pradesh', 22: 'Uttar Pradesh', 23: 'Uttar Pradesh', 24: 'Uttar Pradesh', 25: 'Uttar Pradesh', 26: 'Uttar Pradesh', 27: 'Uttar Pradesh', 28: 'Uttar Pradesh',
            30: 'Rajasthan', 31: 'Rajasthan', 32: 'Rajasthan', 33: 'Rajasthan', 34: 'Rajasthan',
            36: 'Gujarat', 37: 'Gujarat', 38: 'Gujarat', 39: 'Gujarat',
            40: 'Maharashtra', 41: 'Maharashtra', 42: 'Maharashtra', 43: 'Maharashtra', 44: 'Maharashtra',
            45: 'Madhya Pradesh', 46: 'Madhya Pradesh', 47: 'Madhya Pradesh', 48: 'Madhya Pradesh',
            49: 'Chhattisgarh',
            50: 'Telangana', 51: 'Andhra Pradesh', 52: 'Andhra Pradesh', 53: 'Andhra Pradesh',
            56: 'Karnataka', 57: 'Karnataka', 58: 'Karnataka', 59: 'Karnataka',
            60: 'Tamil Nadu', 61: 'Tamil Nadu', 62: 'Tamil Nadu', 63: 'Tamil Nadu', 64: 'Tamil Nadu',
            67: 'Kerala', 68: 'Kerala', 69: 'Kerala',
            70: 'West Bengal', 71: 'West Bengal', 72: 'West Bengal', 73: 'West Bengal', 74: 'West Bengal',
            75: 'Odisha', 76: 'Odisha', 77: 'Odisha',
            78: 'Assam',
            80: 'Bihar', 81: 'Bihar', 82: 'Jharkhand', 83: 'Jharkhand', 84: 'Bihar', 85: 'Bihar'
        };
        return statesByPrefix[prefix] || '';
    }

    function cityFromPincode(pin) {
        const value = String(pin || '');
        const exact = {
            800014: 'Patna',
            380001: 'Ahmedabad'
        };
        if (exact[value]) return exact[value];
        const prefixes = {
            110: 'Delhi',
            302: 'Jaipur',
            380: 'Ahmedabad',
            390: 'Vadodara',
            395: 'Surat',
            400: 'Mumbai',
            411: 'Pune',
            452: 'Indore',
            462: 'Bhopal',
            500: 'Hyderabad',
            560: 'Bengaluru',
            600: 'Chennai',
            641: 'Coimbatore',
            682: 'Kochi',
            695: 'Thiruvananthapuram',
            700: 'Kolkata',
            751: 'Bhubaneswar',
            800: 'Patna'
        };
        return prefixes[value.slice(0, 3)] || '';
    }

    function addVehicleRegistrationCandidates(value, candidates) {
        const digitFix = value => String(value || '').replace(/O/g, '0').replace(/[IL]/g, '1').replace(/S/g, '5').replace(/B/g, '8');
        const normalized = String(value || '')
            .toUpperCase()
            .replace(/[|]/g, '1')
            .replace(/\bO(?=\d)/g, '0')
            .replace(/(?<=\d)O\b/g, '0');

        for (const match of normalized.matchAll(/\b([A-Z]{2})[\s./-]*([0-9O]{1,2})[\s./-]*([A-Z]{1,3})[\s./-]*([0-9O]{4})\b/g)) {
            const state = match[1];
            const district = digitFix(match[2]).padStart(2, '0');
            const series = match[3];
            const number = digitFix(match[4]);
            if (states.includes(state) && series.length >= 2) candidates.push(`${state}${district}${series}${number}`);
        }

        for (const match of normalized.matchAll(/\b([A-Z]{2})[\s./-]*([0-9O]{1,2})[\s./-]*([0-9O]{4})\b/g)) {
            const state = match[1];
            const district = digitFix(match[2]).padStart(2, '0');
            const number = digitFix(match[3]);
            if (states.includes(state)) candidates.push(`${state}${district}${number}`);
        }

        const compact = normalized.replace(/[^A-Z0-9]/g, '');
        for (const state of states) {
            let offset = compact.indexOf(state);
            while (offset !== -1) {
                const tail = compact.slice(offset + 2, offset + 14);
                const match = tail.match(/^([0-9OILS]{1,2})([A-Z]{0,3})([0-9OILSB]{4})/);
                if (match) {
                    const district = digitFix(match[1]).padStart(2, '0');
                    const series = match[2];
                    const number = digitFix(match[3]);
                    if (series.length >= 2) candidates.push(`${state}${district}${series}${number}`);
                }
                offset = compact.indexOf(state, offset + 2);
            }
        }
    }

    function addLikelyGujaratRegistrationCandidates(value, candidates) {
        const digitFix = value => String(value || '').replace(/O/g, '0').replace(/[IL]/g, '1').replace(/S/g, '5').replace(/B/g, '8');
        const letterFix = value => String(value || '').replace(/0/g, 'O').replace(/[18]/g, 'I').replace(/5/g, 'S');
        const compact = String(value || '')
            .toUpperCase()
            .replace(/[|]/g, '1')
            .replace(/[^A-Z0-9]/g, '');

        for (const match of compact.matchAll(/(?:GJ|6J|CJ|G1|GI|9J)([0-9OILS]{1,2})([A-Z]{2,3})([0-9OILSB]{4})/g)) {
            const district = digitFix(match[1]).padStart(2, '0');
            const rawSeries = letterFix(match[2]).replace(/[^A-Z]/g, '');
            const number = digitFix(match[3]);

            if (!/^\d{2}$/.test(district) || Number(district) < 1 || Number(district) > 99) {
                continue;
            }
            if (!/^[A-Z]{2,3}$/.test(rawSeries)) {
                continue;
            }
            if (!/^\d{4}$/.test(number)) {
                continue;
            }

            candidates.push(`GJ${district}${rawSeries}${number}`);
        }

        const stateOffsets = [];
        for (const stateToken of ['GJ', '6J', 'CJ', 'G1', 'GI', '9J']) {
            let offset = compact.indexOf(stateToken);
            while (offset !== -1) {
                stateOffsets.push(offset);
                offset = compact.indexOf(stateToken, offset + 1);
            }
        }

        stateOffsets.sort((a, b) => a - b).forEach(offset => {
            const tail = compact.slice(offset + 2, offset + 18);
            const districtMatch = tail.match(/^([0-9OILS]{1,2})/);
            if (!districtMatch) {
                return;
            }

            const district = digitFix(districtMatch[1]).padStart(2, '0');
            if (!/^\d{2}$/.test(district) || Number(district) < 1 || Number(district) > 99) {
                return;
            }

            const afterDistrict = tail.slice(districtMatch[1].length);
            for (const seriesLength of [2, 3]) {
                const rawSeries = afterDistrict.slice(0, seriesLength);
                if (!/^[A-Z0-9]{2,3}$/.test(rawSeries)) {
                    continue;
                }

                const series = letterFix(rawSeries).replace(/[^A-Z]/g, '');
                const numberSource = afterDistrict.slice(seriesLength).replace(/[^0-9OILSB]/g, '');
                const number = digitFix(numberSource).slice(0, 4);

                if (/^[A-Z]{2,3}$/.test(series) && /^\d{4}$/.test(number)) {
                    candidates.push(`GJ${district}${series}${number}`);
                }
            }
        });
    }

    function addLabelledVehicleRegistrationCandidates(lines, index, candidates) {
        const nearby = [
            lines[index] || '',
            lines[index + 1] || '',
            lines[index + 2] || '',
        ].join(' ');
        const line = lines[index] || '';

        if (!/(?:\bREG\.?\s*(?:NO|N)?\b|\bREGISTRATION\s*(?:NO|NUMBER)\b|\bCERTIFICATE\s+OF\s+REGISTRATION\b|\bVEHICLE\s*(?:NO|NUMBER)\b|\bRC\s*(?:NO|NUMBER)\b)/i.test(line)) {
            return;
        }

        const afterLabel = nearby.replace(/^.*?(?:\bREG\.?\s*(?:NO|N)?\b|\bREGISTRATION\s*(?:NO|NUMBER)\b|\bCERTIFICATE\s+OF\s+REGISTRATION\b|\bVEHICLE\s*(?:NO|NUMBER)\b|\bRC\s*(?:NO|NUMBER)\b)\s*[:.\-]?\s*/i, '');
        addVehicleRegistrationCandidates(afterLabel, candidates);
        addLikelyGujaratRegistrationCandidates(afterLabel, candidates);
    }

    function addStrictVehicleRegistrationCandidates(value, candidates) {
        const digitFix = value => String(value || '').replace(/O/g, '0').replace(/[IL]/g, '1').replace(/S/g, '5').replace(/B/g, '8');
        const normalized = String(value || '')
            .toUpperCase()
            .replace(/[|]/g, '1')
            .replace(/\bO(?=\d)/g, '0')
            .replace(/(?<=\d)O\b/g, '0');

        for (const match of normalized.matchAll(/\b([A-Z]{2})[\s./-]*([0-9OILS]{1,2})[\s./-]*([A-Z]{2,3})[\s./-]*([0-9OILSB]{4})\b/g)) {
            const state = match[1];
            const district = digitFix(match[2]).padStart(2, '0');
            const series = match[3];
            const number = digitFix(match[4]);
            if (states.includes(state)) candidates.push(`${state}${district}${series}${number}`);
        }

        addLikelyGujaratRegistrationCandidates(value, candidates);
    }

    function parse(text, type) {
        const lines = digits(text).split(/[\r\n]+/).map(line => line.replace(/[|]/g, ' ').replace(/\s+/g, ' ').trim()).filter(Boolean);
        const result = {
            driver_name: '',
            child_name: '',
            father_name: '',
            mother_name: '',
            license_no: '',
            license_expiry_date: '',
            adher_no: '',
            father_aadhaar_number: '',
            mother_aadhaar_number: '',
            current_address: '',
            address_1: '',
            address_2: '',
            home_address: '',
            vehicle_number: '',
            rc_number: '',
            rc_expiry_date: '',
            insurance_number: '',
            insurance_expiry_date: '',
            document_vehicle_number: '',
            date_of_birth: '',
            gender: '',
            state: '',
            city: '',
            pincode: '',
            aadhaar_side: 'unknown',
            address_requires_manual_review: false,
            ambiguous: []
        };
        const names = [], numbers = [], expiries = [], rcNumbers = [], rcExpiries = [], insuranceNumbers = [], insuranceExpiries = [], insuranceVehicleNumbers = [], aadhaarAddresses = [];

        lines.forEach((line, index) => {
            if (/^(?:\d[.)]?\s*)?(?:NAME(?: OF (?:THE )?HOLDER)?|HOLDER(?:'S)? NAME)\s*[:.\-]?/i.test(line)) {
                const remainder = line.replace(/^(?:\d[.)]?\s*)?(?:NAME(?: OF (?:THE )?HOLDER)?|HOLDER(?:'S)? NAME)\s*[:.\-]?\s*/i, '');
                names.push(cleanName(remainder || lines[index + 1] || ''));
            }

            if (type === 'aadhaar' && /(?:DOB|DATE OF BIRTH|YEAR OF BIRTH|\u091c\u0928\u094d\u092e|\u0a9c\u0aa8\u0acd\u0aae)/i.test(line) && index > 0) {
                names.push(aadhaarNameLine(lines[index - 1]));
            }

            if (type === 'aadhaar') {
                if (!result.date_of_birth && /(?:DOB|DATE OF BIRTH|D\.?O\.?B|BIRTH)/i.test(line)) {
                    result.date_of_birth = date(line + ' ' + (lines[index + 1] || ''));
                }
                if (!result.gender) {
                    if (/\bFEMALE\b/i.test(line)) result.gender = 'Female';
                    else if (/\bMALE\b/i.test(line)) result.gender = 'Male';
                    else if (/\bOTHER\b/i.test(line)) result.gender = 'Other';
                }
            }

            if (type === 'vehicle-rc') {
                addLabelledVehicleRegistrationCandidates(lines, index, rcNumbers);
                if (/(?:EXPIR[YE]|VALID\s*(?:TILL|TO|UP\s*TO|UNTIL)|VALIDITY|FITNESS|TAX\s*VALID|REGISTRATION\s*VALID)/i.test(line)) {
                    const found = dates(line + ' ' + (lines[index + 1] || ''));
                    const sameLine = dates(line);
                    const candidates = sameLine.length ? sameLine : found;
                    if (candidates.length) rcExpiries.push(candidates[candidates.length - 1]);
                }
                return;
            }

            if (type === 'vehicle-insurance') {
                const nearby = (line + ' ' + (lines[index + 1] || '')).replace(/\s+/g, ' ');
                if (/(?:\bREG(?:ISTRATION|N)?\s*(?:NO|NUMBER|#)?\b|\bVEHICLE\s*(?:NO|NUMBER|#)?\b|\bMOTOR\s+VEHICLE\b)/i.test(line)) {
                    addStrictVehicleRegistrationCandidates(nearby, insuranceVehicleNumbers);
                }
                const labelled = nearby.match(/(?:POLICY\s*\/?\s*INSURANCE|POLICY|POL|INSURANCE|CERTIFICATE|COVER\s*NOTE|PROPOSAL)\s*(?:NO\.?|NUMBER|#)?\s*[:.\-]?\s*([A-Z]{1,6}[\/\s-]*\d[\dA-Z\/\s-]{5,35}|\d[\dA-Z\/\s-]{5,35})/i);
                if (labelled) {
                    const numberPart = labelled[1].split(/\b(?:POLICY|VALID|EXPIRY|EXPIRATION|DATE|FROM|TO|PERIOD|RISK|END|START|TYPE|ISSUE|COMPANY)\b/i)[0];
                    const compact = numberPart.replace(/^\s*(?:NO\.?|NUMBER|#)\s*[:.\-]?\s*/i, '').replace(/[\s/-]/g, '').toUpperCase().replace(/^(?:NO|NUMBER|INSURANCE)/, '');
                    if (/^[A-Z0-9]{6,30}$/.test(compact) && /\d/.test(compact) && !/(?:DATE|VALID|EXPIR|FROM|TO|PERIOD|INSURED|VEHICLE|END|START)/i.test(compact)) insuranceNumbers.push(compact);
                }
                if (/(?:EXPIR[YE]|EXPIRATION|VALID\s*(?:TILL|TO|UP\s*TO|UNTIL)|VALIDITY|POLICY\s*(?:END|TO|PERIOD)|RISK\s*(?:END|TO)|PERIOD\s+OF\s+INSURANCE|TO\s+MIDNIGHT|MIDNIGHT\s+OF)/i.test(line)) {
                    const found = dates(line + ' ' + (lines[index + 1] || ''));
                    const sameLine = dates(line);
                    const candidates = sameLine.length ? sameLine : found;
                    if (candidates.length) insuranceExpiries.push(candidates[candidates.length - 1]);
                }
                return;
            }

            if (type !== 'license') return;

            for (const match of line.toUpperCase().matchAll(/\b([A-Z]{2})[\s/-]?(\d[\d\s/-]{7,22}\d)\b/g)) {
                const compact = match[1] + match[2].replace(/[^0-9]/g, '');
                if (states.includes(match[1]) && compact.length >= 10 && compact.length <= 18) numbers.push(compact);
            }

            const labelled = line.match(/(?:D\.?\s*L\.?|LICEN[CS]E)\s*(?:NO\.?|NUMBER|#)?\s*[:.\-]?\s*([A-Z]{2}[A-Z0-9 /-]+)/i);
            if (labelled) {
                const compact = labelled[1].replace(/[\s/-]/g, '').toUpperCase();
                if (/^[A-Z]{2}[0-9]{8,16}$/.test(compact) && states.includes(compact.slice(0, 2))) numbers.push(compact);
            }

            if (/(?:EXPIR[YE]|EXPIRATION|VALID\s*(?:TILL|TO|UP\s*TO|UNTIL)|VALIDITY|VALID THRU|NT\s*(?:VALID|:)|TR\s*(?:VALID|:))/i.test(line)) {
                const found = dates(line + ' ' + (lines[index + 1] || ''));
                const sameLine = dates(line);
                const candidates = sameLine.length ? sameLine : found;
                if (candidates.length) expiries.push(candidates[candidates.length - 1]);
            }
        });

        function assign(field, values) {
            const candidates = unique(values);
            if (candidates.length === 1) result[field] = candidates[0];
            else if (candidates.length > 1) result.ambiguous.push(field);
        }

        if (type === 'license') {
            assign('driver_name', names);
            assign('license_no', numbers);
            assign('license_expiry_date', expiries);
            return result;
        }

        if (type === 'vehicle-rc') {
            if (!rcNumbers.length) {
                lines.forEach(line => {
                    if (/\b(?:CHASSIS|ENGINE|OWNER|ADDRESS|FUEL|CLASS|DATE|VALID|VALIDITY|REG\.?\s*VALIDITY)\b/i.test(line)) {
                        return;
                    }
                    addStrictVehicleRegistrationCandidates(line, rcNumbers);
                });
            }
            if (!rcNumbers.length && /\bGUJARAT\b/i.test(lines.join(' '))) {
                lines.forEach(line => {
                    if (/\b(?:CHASSIS|ENGINE|OWNER|ADDRESS|FUEL|CLASS|DATE|VALID|VALIDITY|REG\.?\s*VALIDITY)\b/i.test(line)) {
                        return;
                    }
                    addLikelyGujaratRegistrationCandidates(line, rcNumbers);
                });
            }
            const registrationCandidates = unique(rcNumbers);
            if (registrationCandidates.length) {
                result.vehicle_number = registrationCandidates[0];
                result.rc_number = registrationCandidates[0];
            }
            assign('rc_expiry_date', rcExpiries);
            return result;
        }

        if (type === 'vehicle-insurance') {
            const numberCandidates = unique(insuranceNumbers)
                .filter(value => /\d/.test(value))
                .sort((a, b) => b.length - a.length);
            if (numberCandidates.length) {
                result.insurance_number = numberCandidates[0];
            }

            const expiryCandidates = unique(insuranceExpiries);
            if (expiryCandidates.length) {
                result.insurance_expiry_date = expiryCandidates[expiryCandidates.length - 1];
            }
            const insuranceVehicleCandidates = unique(insuranceVehicleNumbers);
            if (insuranceVehicleCandidates.length) {
                // Candidates come from OCR-tolerant variants of the same
                // labelled registration number.  The strict extractor adds
                // the literal match first, which is the safest value here.
                result.document_vehicle_number = insuranceVehicleCandidates[0];
            }
            return result;
        }

        const aadhaar = [];
        const aadhaarLineIndexes = [];
        lines.forEach((line, index) => {
            for (const match of line.matchAll(/(?<!\d)([2-9]\d{3})[ -]?(\d{4})[ -]?(\d{4})(?!\d)/g)) {
                aadhaar.push(`${match[1]} ${match[2]} ${match[3]}`);
                aadhaarLineIndexes.push(index);
            }
        });

        aadhaarAddresses.push(...extractAadhaarAddresses(lines));
        if (lines.some(hasAadhaarAddressLabel) && !aadhaarAddresses.length) {
            result.address_requires_manual_review = true;
        }
        const addressText = aadhaarAddresses.join(' ');
        const fullAadhaarText = lines.join(' ');
        // An Aadhaar front normally carries DOB/year of birth and gender.  The
        // reverse carries the postal address.  Keep this deliberately
        // conservative: an unreadable document must be uploaded again rather
        // than silently being accepted in the wrong slot.
        const hasFrontDetails = /\b(?:DOB|D\.?O\.?B\.?|DATE OF BIRTH|YEAR OF BIRTH|MALE|FEMALE|OTHER)\b|जन्म|पुरुष|महिला/i.test(fullAadhaarText);
        const hasBackDetails = /\b(?:ADDRESS|PIN(?:CODE)?|POST(?:AL)?\s*CODE)\b|पता/i.test(fullAadhaarText)
            || /\b[1-9]\d{5}\b/.test(addressText || fullAadhaarText);
        if (hasFrontDetails) result.aadhaar_side = 'front';
        else if (hasBackDetails) result.aadhaar_side = 'back';
        const pinMatch = addressText.match(/\b([1-9]\d{5})\b/) || fullAadhaarText.match(/\b([1-9]\d{5})\b/);
        if (pinMatch) result.pincode = pinMatch[1];
        const foundState = indianStates.find(state => new RegExp('\\b' + state.replace(/\s+/g, '\\s+') + '\\b', 'i').test(addressText));
        if (foundState) result.state = foundState;
        else result.state = stateFromPincode(result.pincode);
        const foundCity = commonCities.find(city => new RegExp('\\b' + city + '\\b', 'i').test(addressText));
        if (foundCity) result.city = foundCity;
        else result.city = cityFromPincode(result.pincode);

        aadhaarLineIndexes.forEach(index => {
            for (let previous = index - 1; previous >= Math.max(0, index - 5); previous--) {
                const candidate = aadhaarNameLine(lines[previous]);
                if (candidate) {
                    names.push(candidate);
                    break;
                }
            }
        });
        if (!names.length) {
            lines.forEach(line => {
                const candidate = aadhaarNameLine(line);
                if (candidate) names.push(candidate);
            });
        }

        const nameCandidates = unique(names);
        if (nameCandidates.length) {
            result.driver_name = nameCandidates[0];
            result.child_name = nameCandidates[0];
            result.father_name = nameCandidates[0];
            result.mother_name = nameCandidates[0];
        }
        assign('adher_no', aadhaar);
        assign('father_aadhaar_number', aadhaar);
        assign('mother_aadhaar_number', aadhaar);
        assign('current_address', aadhaarAddresses);
        const primaryAddress = unique(aadhaarAddresses)[0] || '';
        if (primaryAddress) {
            const split = splitAddress(primaryAddress);
            result.address_1 = split[0];
            result.address_2 = split[1];
        }
        assign('home_address', aadhaarAddresses);
        return result;
    }

    return {parse, date};
});
