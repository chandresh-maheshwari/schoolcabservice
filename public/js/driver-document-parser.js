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
        const match = digits(value).match(/\b(?:(\d{4})[-/.](\d{1,2})[-/.](\d{1,2})|(\d{1,2})[-/.\s]+(\d{1,2}|JAN(?:UARY)?|FEB(?:RUARY)?|MAR(?:CH)?|APR(?:IL)?|MAY|JUN(?:E)?|JUL(?:Y)?|AUG(?:UST)?|SEP(?:TEMBER)?|OCT(?:OBER)?|NOV(?:EMBER)?|DEC(?:EMBER)?)[-/.,\s]+(\d{2}|\d{4})|(JAN(?:UARY)?|FEB(?:RUARY)?|MAR(?:CH)?|APR(?:IL)?|MAY|JUN(?:E)?|JUL(?:Y)?|AUG(?:UST)?|SEP(?:TEMBER)?|OCT(?:OBER)?|NOV(?:EMBER)?|DEC(?:EMBER)?)\s+(\d{1,2})(?:ST|ND|RD|TH)?\s*,?\s*(\d{2}|\d{4}))\b/i);
        if (!match) return '';
        const rawYear = String(match[1] || match[6] || match[9]);
        const year = rawYear.length === 2 ? 2000 + Number(rawYear) : Number(rawYear);
        const monthText = match[2] || match[5] || match[7];
        const month = /^\d+$/.test(monthText) ? Number(monthText) : 'JAN FEB MAR APR MAY JUN JUL AUG SEP OCT NOV DEC'.split(' ').indexOf(monthText.slice(0, 3).toUpperCase()) + 1;
        const day = Number(match[3] || match[4] || match[8]);
        const parsed = new Date(year, month - 1, day);
        if (year < 1900 || year > 2199 || parsed.getFullYear() !== year || parsed.getMonth() !== month - 1 || parsed.getDate() !== day) return '';
        return `${String(day).padStart(2, '0')}/${String(month).padStart(2, '0')}/${year}`;
    }

    function dates(value) {
        return [...digits(value).matchAll(/\b(?:\d{4}[-/.]\d{1,2}[-/.]\d{1,2}|\d{1,2}[-/.\s]+(?:\d{1,2}|[A-Z]{3,9})[-/.,\s]+(?:\d{2}|\d{4})|[A-Z]{3,9}\s+\d{1,2}(?:ST|ND|RD|TH)?\s*,?\s*\d{2,4})\b/gi)].map(match => date(match[0])).filter(Boolean);
    }

    function latestDate(values) {
        return unique(values).sort((left, right) => {
            const [leftDay, leftMonth, leftYear] = left.split('/').map(Number);
            const [rightDay, rightMonth, rightYear] = right.split('/').map(Number);
            return new Date(leftYear, leftMonth - 1, leftDay) - new Date(rightYear, rightMonth - 1, rightDay);
        }).pop() || '';
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

    function bestAadhaarName(values, preferredValues, evidenceLines) {
        const candidates = unique(values).filter(Boolean);
        if (!candidates.length) return '';
        const observations = new Map();
        const evidence = evidenceLines ? evidenceLines.map(aadhaarNameLine).filter(Boolean) : values.filter(Boolean);
        evidence.forEach(value => {
            const key = value.toLowerCase();
            observations.set(key, (observations.get(key) || 0) + 1);
        });
        unique(evidence).forEach(base => {
            if (observations.get(base.toLowerCase()) < 2) return;
            if (candidates.some(value => value.toLowerCase().startsWith(base.toLowerCase() + ' ')
                && /^(?:[A-Z]{1,2}\.?\s*){1,2}$/.test(value.slice(base.length).trim()))) candidates.push(base);
        });
        const preferred = new Set(preferredValues || []);
        return candidates.sort((left, right) => {
            const score = value => {
                const words = value.split(/\s+/);
                const first = words[0] || '';
                const second = words[1] || '';
                // A noisy read may append a short token to an otherwise
                // consistently read complete name. Prefer the independently
                // repeated name; never strip initials just by their spelling.
                const unsupportedSuffix = candidates.some(base => {
                    if (base.split(/\s+/).filter(word => word.length > 2).length < 2) return false;
                    if (!value.toLowerCase().startsWith(base.toLowerCase() + ' ')) return false;
                    const suffix = value.slice(base.length).trim();
                    return /^(?:[A-Z]{1,2}\.?\s*){1,2}$/.test(suffix)
                        && observations.get(base.toLowerCase()) >= 2
                        && observations.get(base.toLowerCase()) > observations.get(value.toLowerCase());
                });
                return (words.length * 20)
                    + Math.min(value.length, 90)
                    + (preferred.has(value) ? 35 : 0)
                    + Math.min(3, Math.max(0, (observations.get(value.toLowerCase()) || 1) - 1)) * 50
                    - (unsupportedSuffix ? 100 : 0)
                    - (first.length > 2 ? words.slice(1, -1).filter(word => /^[A-Za-z]$/.test(word)).length * 40 : 0)
                    - (/^[A-Za-z]{1,2}$/.test(first) && second.length > 2 ? 45 : 0);
            };
            return score(right) - score(left);
        })[0];
    }

    function hasAadhaarAddressLabel(line) {
        return /(?:ADDRESS|ADDR(?:ESS)?|C\/O|S\/O|D\/O|W\/O|CARE OF|SON OF|DAUGHTER OF|WIFE OF|पता|पत्ता|સરનામું|સરનામુ|સરનામા|ঠিকানা|ਪਤਾ|முகவரி|చిరునామా|ವಿಳಾಸ|വിലാസം|پتہ)/i.test(String(line || ''));
    }

    function cleanAddressLine(line) {
        const labelRegex = new RegExp('^.*?' + aadhaarAddressLabelPattern + '\\s*[:.\\-]?\\s*', 'iu');
        const value = String(line || '')
            .replace(labelRegex, match => {
                const relation = match.match(/(?:C\/O|S\/O|D\/O|W\/O|CARE OF|SON OF|DAUGHTER OF|WIFE OF)\s*[:.\-]?\s*$/i);
                return relation ? relation[0] : '';
            })
            .replace(/\b(?:MOBILE|PHONE|VID)\b.*$/i, '')
            .replace(/[^\p{L}\p{M}0-9,./#()\- ]+/gu, ' ')
            .replace(/\s+/g, ' ')
            .trim();
        if (!value || value.length < 1) return '';
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
            for (let next = index + 1; next < Math.min(lines.length, index + 16); next++) {
                if (parts.some(part => /\b[1-9]\d{5}\b/.test(part))) break;
                const raw = lines[next];
                if (hasAadhaarAddressLabel(raw) && !/^(?:C\/O|S\/O|D\/O|W\/O|CARE OF|SON OF|DAUGHTER OF|WIFE OF)\b/i.test(raw)) break;
                if (/(?<!\d)[2-9]\d{3}[ -]?\d{4}[ -]?\d{4}(?!\d)|\b(?:VID|AADHAAR|GOVERNMENT|UNIQUE|IDENTIFICATION|AUTHORITY|DOB|DATE OF BIRTH|YEAR OF BIRTH|MALE|FEMALE)\b/i.test(raw)) break;
                const cleaned = cleanAddressLine(raw);
                if (!cleaned) break;
                parts.push(cleaned);
            }
            const address = parts.join(', ').replace(/\s*,\s*/g, ', ').replace(/(?:,\s*){2,}/g, ', ').trim();
            if (address.length >= 8 && address.length <= 600 && isReadableAddress(address)) addresses.push(address);
        });
        return unique(addresses).sort((left, right) => {
            const score = value => {
                const fragments = value.split(/\s*,\s*/);
                const noise = fragments.filter(part => /^(?:\d{1,3}|[A-Za-z]{1,2})$/.test(part.trim())).length;
                return (/\b[1-9]\d{5}\b/.test(value) ? 1000 : 0) + value.length - noise * 100;
            };
            return score(right) - score(left);
        });
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

    function cityFromAddress(address, state) {
        const explicit = address.match(/\b(?:CITY|TOWN)\s*[:.-]?\s+([^,]+)/i);
        let candidate = explicit ? explicit[1] : '';
        if (!candidate && state) {
            const stateMatch = new RegExp('\\b' + state.replace(/\s+/g, '\\s+') + '\\b', 'i').exec(address);
            if (stateMatch) {
                const beforeState = address.slice(0, stateMatch.index).replace(/[,\s-]+$/, '');
                const parts = beforeState.split(',').map(part => part.trim()).filter(Boolean);
                if (parts.length >= 2) candidate = parts[parts.length - 1];
            }
        }
        candidate = candidate.trim();
        if (!/^[A-Za-z][A-Za-z .'-]{1,59}$/.test(candidate)
            || /\b(?:ROAD|STREET|HOUSE|FLAT|BLOCK|NEAR|COLONY|S\/O|C\/O|NAGAR|DISTRICT|TALUKA|POST)\b/i.test(candidate)) return '';
        return /^Ahm[ae]dabad$/i.test(candidate) ? 'Ahmedabad' : candidate;
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

    function cleanInsurancePolicyNumber(value) {
        const candidate = String(value || '')
            .split(/\s+(?=(?:\d+\s*(?:W|YEAR|MONTH)\b|LIABILITY\b|COMPREHENSIVE\b|PACKAGE\b))/i)[0]
            .split(/(?:POLICY\s*HOLDER|POLICYHOLDER|POLICY|PROVISIONS?|CLAIMS?|TERMS?|CONDITIONS?|SECTIONS?|\b(?:HOLDER|INSURED|CUSTOMER|NAME|VALID|EXPIRY|EXPIRATION|DATE|FROM|TO|PERIOD|RISK|END|START|TYPE|ISSUE|COMPANY)\b)/i)[0]
            .replace(/^\s*(?:NO\.?|NUMBER|#)\s*[:.\-]?\s*/i, '')
            .replace(/[\s/-]/g, '')
            .toUpperCase()
            .replace(/^(?:NO|NUMBER|INSURANCE)/, '');
        if (!/^[A-Z0-9]{6,30}$/.test(candidate) || !/\d/.test(candidate)) return '';
        if (/(?:DATE|VALID|EXPIR|PERIOD|INSURED|VEHICLE|HOLDER|POLICYHOLDER|PROVISION|CLAIM|CONDITION|SECTION)/i.test(candidate)) return '';
        return candidate;
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
            child_aadhaar_number: '',
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
        const names = [], aadhaarDobNames = [], numbers = [], labelledLicenseNumbers = [], expiries = [], generalLicenseExpiries = [], classLicenseExpiries = [], rcNumbers = [], rcExpiries = [], insuranceNumbers = [], insuranceExpiries = [], insuranceVehicleNumbers = [], aadhaarAddresses = [];

        lines.forEach((line, index) => {
            if (/^(?:\d[.)]?\s*)?(?:NAME(?: OF (?:THE )?HOLDER)?|HOLDER(?:'S)? NAME)\s*[:.\-]?/i.test(line)) {
                const remainder = line.replace(/^(?:\d[.)]?\s*)?(?:NAME(?: OF (?:THE )?HOLDER)?|HOLDER(?:'S)? NAME)\s*[:.\-]?\s*/i, '');
                names.push(cleanName(remainder || lines[index + 1] || ''));
            }

            if (type === 'aadhaar' && /(?:DOB|DATE OF BIRTH|YEAR OF BIRTH|\u091c\u0928\u094d\u092e|\u0a9c\u0aa8\u0acd\u0aae)/i.test(line) && index > 0) {
                const dobName = aadhaarNameLine(lines[index - 1]);
                names.push(dobName);
                if (dobName) aadhaarDobNames.push(dobName);
            }

            if (type === 'license' && !result.date_of_birth && /\b(?:DOB|BIRTH)\b/i.test(line)) {
                result.date_of_birth = date(line + ' ' + (lines[index + 1] || ''));
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
                const previousPolicy = value => /\b(?:PREV(?:IOUS)?\.?|PRIOR|OLD|EARLIER)\s*POLICY\b/i.test(value);
                const previousPolicyLine = position => previousPolicy(lines[position] || '')
                    || (position > 0 && /^\s*(?:PREV(?:IOUS)?\.?|PRIOR|OLD|EARLIER)\s*POLICY\s*[:.-]?\s*$/i.test(lines[position - 1]));
                if (previousPolicyLine(index)) return;
                const nearby = (line + ' ' + (previousPolicyLine(index + 1) ? '' : (lines[index + 1] || ''))).replace(/\s+/g, ' ');
                const dateWindow = lines.slice(index, index + 5)
                    .filter((value, offset) => !previousPolicyLine(index + offset)).join(' ');
                if (/(?:\bREG(?:ISTRATION|N)?\s*(?:NO|NUMBER|#)?\b|\bVEHICLE\s*(?:NO|NUMBER|#)?\b|\bMOTOR\s+VEHICLE\b)/i.test(line)) {
                    addStrictVehicleRegistrationCandidates(nearby, insuranceVehicleNumbers);
                }
                const labelled = nearby.match(/(?:\b(?:POLICY\s*\/?\s*INSURANCE|POLICY|POL|CERTIFICATE|COVER\s*NOTE|PROPOSAL)\b\s*(?:NO\.?|NUMBER|#)?|\bINSURANCE\s*(?:NO\.?|NUMBER|#))\s*[:.\-]?\s*([A-Z0-9][A-Z0-9\/\s-]{5,60})/i);
                if (labelled) {
                    const compact = cleanInsurancePolicyNumber(labelled[1]);
                    if (compact) insuranceNumbers.push(compact);
                }
                if (/(?:EXPIR[YE]|EXPIRATION|VALID\s*(?:TILL|TO|UP\s*TO|UNTIL)|VALIDITY|POLICY\s*(?:END|TO|PERIOD)|RISK\s*(?:END|TO)|PERIOD\s+OF\s+INSURANCE|TO\s+MIDNIGHT|MIDNIGHT\s+OF)/i.test(line)) {
                    const candidates = dates(dateWindow);
                    if (candidates.length) insuranceExpiries.push(latestDate(candidates));
                }
                return;
            }

            if (type !== 'license') return;

            for (const match of line.toUpperCase().matchAll(/\b([A-Z]{2})[\s/-]?(\d[\d\s/-]{7,22}\d)\b/g)) {
                const compact = match[1] + match[2].replace(/[^0-9]/g, '');
                if (states.includes(match[1]) && compact.length >= 10 && compact.length <= 18) numbers.push(compact);
            }

            // Some Indian driving licences contain letters after the state
            // and RTO code and are printed without a "DL No" label.
            for (const match of line.toUpperCase().matchAll(/\b([A-Z]{2})[\s/-]?([A-Z0-9]{8,16})\b/g)) {
                const compact = match[1] + match[2];
                const digitCount = (compact.match(/\d/g) || []).length;
                if (states.includes(match[1]) && digitCount >= 6) numbers.push(compact);
            }

            const labelled = (line + ' ' + (lines[index + 1] || '')).match(/(?:D\.?\s*L\.?|LICEN[CS]E)\s*(?:NO\.?|NUMBER|#)?\s*[:.\-]?\s*([A-Z0-9][A-Z0-9 /-]{7,23})/i);
            if (labelled) {
                const compact = labelled[1]
                    .split(/\s+(?:VALID|VALIDITY|EXPIRY|DATE|NAME|DOB|ADDRESS)\b/i)[0]
                    .replace(/[\s/-]/g, '')
                    .toUpperCase();
                const statePrefixed = /^[A-Z]{2}[A-Z0-9]{8,16}$/.test(compact)
                    && states.includes(compact.slice(0, 2))
                    && (compact.match(/\d/g) || []).length >= 6;
                const numericOnly = /^[0-9]{8,18}$/.test(compact);
                if (statePrefixed || numericOnly) {
                    numbers.push(compact);
                    labelledLicenseNumbers.push(compact);
                }
            }

            if (/(?:DATE\s+OF\s+EXPIRY|EXPIRY\s*(?:DATE|DT\.?)?|EXPIR[YE]|EXPIRATION|VALID\s*(?:TILL|TO|UP\s*TO|UNTIL|THRU|THROUGH)|VALIDITY|NT\s*(?:VALID|:)|TR\s*(?:VALID|:))/i.test(line) || hasLicenseExpiryLabel(line)) {
                const found = dates(lines.slice(index, index + 3).join(' '));
                const sameLine = dates(line);
                const candidates = sameLine.length ? sameLine : found;
                if (candidates.length) {
                    const expiry = latestDate(candidates);
                    expiries.push(expiry);
                    if (/\b(?:NT|TR)\s*(?:VALID|:)/i.test(line)) classLicenseExpiries.push(expiry);
                    else generalLicenseExpiries.push(expiry);
                }
            }
        });

        function assign(field, values) {
            const candidates = unique(values);
            if (candidates.length === 1) result[field] = candidates[0];
            else if (candidates.length > 1) result.ambiguous.push(field);
        }

        if (type === 'license') {
            result.document_number_candidates = unique(numbers);
            assign('driver_name', names);
            const preferredLicenseNumbers = unique(labelledLicenseNumbers);
            if (preferredLicenseNumbers.length) {
                result.license_no = preferredLicenseNumbers[0];
            } else {
                assign('license_no', numbers);
            }
            if (generalLicenseExpiries.length) {
                result.license_expiry_date = latestDate(generalLicenseExpiries);
            } else {
                assign('license_expiry_date', classLicenseExpiries.length ? classLicenseExpiries : expiries);
            }
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
                result.insurance_expiry_date = latestDate(expiryCandidates);
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
            // VID is a separate 16-digit identifier, never an Aadhaar number.
            const numberLine = line.split(/\bVID\b\s*[:.\-]?/i)[0]
                .replace(/(?<!\d)\d{4}[ -]?\d{4}[ -]?\d{4}[ -]?\d{4}(?!\d)/g, '');
            for (const match of numberLine.matchAll(/(?<!\d)([2-9]\d{3})[ -]?(\d{4})[ -]?(\d{4})(?!\d)/g)) {
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
        const foundCity = [...commonCities, 'Pali'].find(city => {
            const spelling = city === 'Ahmedabad' ? 'Ahm[ae]dabad' : city;
            return new RegExp('\\b' + spelling + '\\b', 'i').test(addressText);
        });
        const printedCity = cityFromAddress(aadhaarAddresses[0] || '', result.state);
        if (printedCity) result.city = printedCity;
        else if (foundCity) result.city = foundCity;
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

        const aadhaarName = bestAadhaarName(names, aadhaarDobNames, lines);
        if (aadhaarName) {
            result.driver_name = aadhaarName;
            result.child_name = aadhaarName;
            result.father_name = aadhaarName;
            result.mother_name = aadhaarName;
        }
        assign('adher_no', aadhaar);
        assign('child_aadhaar_number', aadhaar);
        assign('father_aadhaar_number', aadhaar);
        assign('mother_aadhaar_number', aadhaar);
        const primaryAddress = unique(aadhaarAddresses)[0] || '';
        if (primaryAddress) {
            result.current_address = primaryAddress;
            result.home_address = primaryAddress;
            const split = splitAddress(primaryAddress);
            result.address_1 = split[0];
            result.address_2 = split[1];
        }
        return result;
    }

    // Validate independent document evidence before exposing extracted values.
    // A number, name or expiry date alone does not identify a document type.
    function validateDocument(text, type, side, resolvedValues) {
        const content = String(text || '').replace(/\s+/g, ' ');
        const parsed = resolvedValues || parse(text, type);
        const licenseBack = /\b(?:COV|CLASS\s+OF\s+VEHICLE|VEHICLES?\s+AUTHORI[ZS]ED|ENDORSEMENT|BADGE|HOLDER'?S?\s+SIGNATURE|LMV|MCWG|MCWOG|HMV)\b/i.test(content)
            && (/\b(?:LICEN[CS]ING\s+AUTHORITY|ISSUING\s+AUTHORITY|FORM\s*7|RULE\s*16|HOLDER'?S?\s+SIGNATURE|CLASS\s+OF\s+VEHICLE|COV)\b/i.test(content)
                || (/\bLMV\b/i.test(content) && /\bMCW(?:G|OG)\b/i.test(content) && /\bHOLDER'?S?\b/i.test(content)));
        const license = /\b(?:DRIVING\s+LICEN[CS]E|DRIVER'?S?\s+LICEN[CS]E|LICEN[CS]E\s*(?:NO|NUMBER)|D\.?\s*L\.?\s*(?:NO|NUMBER))\b/i.test(content) || licenseBack;
        const rcTechnical = /\bCHASSIS\b/i.test(content) && /\bENGINE\b/i.test(content)
            && /\b(?:CUBIC\s+CAPACITY|SEATING\s+CAPACITY|UNLADEN\s+WEIGHT|GROSS\s+VEHICLE\s+WEIGHT)\b/i.test(content);
        const rcBackDetails = [/\bSEATING\s+CAPACITY\b/i, /\bCUBIC\s+CAPACITY\b/i,
            /\bWHEEL\s*BASE\b/i, /\bCYLINDER\s*(?:NO|NUMBER)\b/i,
            /\bFINANC[EI]R\s+NAME\b/i, /\bBODY\s+TYPE\b/i].filter(pattern => pattern.test(content)).length;
        const rcBackLayout = rcBackDetails >= 2 && (Boolean(parsed.rc_number)
            || /\b(?:REGISTRATION\s+AUTHORITY|FORM\s*23\s*A?|MAKER'?S?\s+NAME)\b/i.test(content));
        const rc = rcTechnical || rcBackLayout || /\b(?:CERTIFICATE\s+OF\s+REGISTRATION|REGISTRATION\s+CERTIFICATE|RC\s+BOOK)\b/i.test(content)
            || (/\bCHASSIS\b/i.test(content) && /\bENGINE\b/i.test(content) && /\b(?:REGN|REGISTRATION|OWNER)\b/i.test(content));
        // RCs and licences may mention insurance in instructions. That word
        // alone must not classify them as an insurance policy.
        const insurance = /\b(?:INSURER|POLICY\s*(?:NO|NUMBER)|CERTIFICATE\s+OF\s+INSURANCE|INSURANCE\s+(?:POLICY|CERTIFICATE))\b/i.test(content);
        // Faded printing can lose letters in INDIA. Accept a close OCR
        // spelling only alongside the Aadhaar number, DOB and gender;
        // a partial government heading alone is never sufficient.
        const fadedIndianHeading = [...content.matchAll(/\bGOVERNMENT\s+OF\s+([A-Z01]{3,7})\b/gi)].some(match => {
            const country = match[1].toLowerCase().replace(/1/g, 'i').replace(/0/g, 'o');
            return /^i[a-z]+a$/.test(country) && ocrLabelDistance(country, 'india') <= 3;
        });
        const aadhaar = /\b(?:AADHAAR|AADHAR|UIDAI|UNIQUE\s+IDENTIFICATION)\b|आधार/i.test(content)
            || (/\b(?:GOV(?:ERNMEN)?T\.?\s*(?:OF\s+)?INDIA|UNIQUE\s+IDENTIFICATION\s+AUTHORITY)\b|\u092d\u093e\u0930\u0924\s*\u0938\u0930\u0915\u093e\u0930|\u0aad\u0abe\u0ab0\u0aa4\s*\u0ab8\u0ab0\u0a95\u0abe\u0ab0|\u0a86\u0aa7\u0abe\u0ab0/i.test(content) && Boolean(parsed.adher_no));
        let valid = false;
        if (type === 'aadhaar') {
            const supportedFadedHeading = fadedIndianHeading && parsed.date_of_birth && parsed.gender;
            // On worn backs OCR can lose "Unique Identification" and join
            // the remaining authority wording. Require address evidence as
            // well as the number; the paired number is checked separately.
            const partialBackIssuer = parsed.aadhaar_side === 'back' && parsed.pincode
                && /AUTHO?R[I1l]?(?:TY|ITY)\s*OF\s*[I1l]ND[I1l]A/i.test(content);
            valid = Boolean((aadhaar || supportedFadedHeading || partialBackIssuer) && !license && !rc && !insurance && parsed.adher_no && parsed.aadhaar_side === side);
        }
        if (type === 'license') {
            const front = /\b(?:NAME|DOB|DATE\s+OF\s+BIRTH)\b/i.test(content);
            const back = licenseBack;
            valid = license && !rc && !insurance && Boolean(parsed.license_no)
                && (side === 'back' ? back && !front : front);
        }
        if (type === 'vehicle-rc') {
            const front = /\b(?:OWNER|REGN|REGISTRATION)\b/i.test(content);
            const back = /\b(?:CHASSIS|ENGINE|HYPOTHECAT|FINANCIER|SEATING|UNLADEN|CUBIC)\b/i.test(content);
            // Technical fields can also occur on an RC front. They alone
            // must not allow that front to pass as a reverse image.
            const ownerDetails = /\bOWNER(?:'S)?\s*(?:NAME|:)|\bNAME\s+OF\s+(?:THE\s+)?OWNER\b/i.test(content);
            valid = rc && !license && !insurance && !aadhaar
                && (side === 'back' ? (back || rcBackLayout) && !ownerDetails && (Boolean(parsed.rc_number) || rcTechnical)
                    : front && Boolean(parsed.rc_number) && (!rcBackLayout || ownerDetails));
        }
        if (type === 'vehicle-insurance') {
            // Policies may list the insured's Aadhaar and a driving-licence
            // clause. An explicit insurance certificate/schedule identifies
            // the document more reliably than those incidental references.
            const insuranceHeading = /\b(?:CERTIFICATE\s+OF\s+INSURANCE|CERTIFICATE\s+CUM\s+SCHEDULE\s+OF\s+INSURANCE|(?:MOTOR\s+)?INSURANCE\s+POLICY|POLICY\s+SCHEDULE)\b/i.test(content);
            valid = insurance && Boolean(parsed.insurance_number)
                && (insuranceHeading || (!license && !aadhaar));
        }
        return {valid, message: valid ? '' : `Please upload valid document. Upload a clear ${type === 'vehicle-rc' ? 'RC book' : type === 'license' ? 'driving license' : type === 'aadhaar' ? 'Aadhaar card' : 'vehicle insurance'} ${side} image in this field.`};
    }

    function matchingSideNumber(side, number, siblingNumber, siblingSelected, savedNumber) {
        const normalize = value => String(value || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
        // Either uploaded side must match its selected, verified counterpart.
        const expected = siblingSelected ? normalize(siblingNumber)
            : !siblingSelected ? normalize(savedNumber) : '';
        return !expected || normalize(number) === expected;
    }

    function repeatedAadhaarNumber(text) {
        const counts = new Map();
        for (const line of digits(text).split(/[\r\n]+/)) {
            // Only isolated printed-number lines participate. Never vote on
            // VID, dates or numbers embedded in other text.
            const match = line.trim().match(/^([2-9]\d{3})\s*(\d{4})\s*(\d{4})$/);
            if (!match) continue;
            const value = `${match[1]} ${match[2]} ${match[3]}`;
            counts.set(value, (counts.get(value) || 0) + 1);
        }
        const ranked = [...counts].sort((a,b) => b[1] - a[1]);
        const total = ranked.reduce((sum, entry) => sum + entry[1], 0);
        return ranked.length && ranked[0][1] >= 3 && ranked[0][1] > total / 2 ? ranked[0][0] : '';
    }

    function ocrLabelDistance(a, b) {
        let row = Array.from({length: b.length + 1}, (_, index) => index);
        for (let i = 0; i < a.length; i++) {
            const next = [i + 1];
            for (let j = 0; j < b.length; j++) next.push(Math.min(next[j] + 1, row[j + 1] + 1, row[j] + (a[i] === b[j] ? 0 : 1)));
            row = next;
        }
        return row[b.length];
    }
    function hasLicenseExpiryLabel(text) {
        return String(text).toLowerCase().split(/\s+/).some(word => {
            const token = word.replace(/[^a-z]/g, '');
            return token.length >= 5 && ['validity', 'expiry', 'expiration'].some(label => ocrLabelDistance(token, label) <= 2);
        });
    }
    function licenseNameRegions(tsv) {
        const words = String(tsv || '').trim().split('\n').slice(1).map(line => line.split('\t'))
            .filter(word => word[0] === '5' && word[11] && Number(word[8]) > 0 && Number(word[9]) > 0);
        return words.filter(word => /^NAME[:.]?$/i.test(word[11])).map(word => ({
            x: Number(word[6]), y: Number(word[7]), height: Number(word[9]),
        }));
    }
    function licenseValidityRegions(tsv) {
        const words = String(tsv || '').trim().split('\n').slice(1).map(line => line.split('\t'))
            .filter(word => word[0] === '5' && word[11] && Number(word[8]) > 0 && Number(word[9]) > 0);
        const labels = words.filter(word => hasLicenseExpiryLabel(word[11]));
        // A damaged digit (e.g. an impossible year) must not hide the region:
        // the image will be read again before any value is accepted.
        const datedWords = words.filter(word => date(word[11]) || /\b\d{1,2}[/.\-]\d{1,2}[/.\-]\d{4}\b/.test(word[11]));
        const labelled = labels.flatMap(label => {
            const x = Number(label[6]), y = Number(label[7]), h = Number(label[9]);
            const candidates = datedWords.filter(word => Number(word[6]) >= x - h
                && Number(word[7]) >= y - h && Number(word[7]) <= y + h * 5)
                .sort((a,b) => Math.abs(Number(a[7]) - y) - Math.abs(Number(b[7]) - y));
            const found = candidates[0];
            return found ? [{x: Number(found[6]), y: Number(found[7]), width: Number(found[8]), height: Number(found[9]), labelled: true}] : [];
        });
        if (labelled.length) return labelled;
        // Some smart cards print Issue and Validity in adjacent columns.
        // When the validity heading is obscured by a security line, require
        // the issue label/date and a distinct, older birth-date row before
        // revisiting the adjacent date as a potential validity date.
        const issueLabels = words.filter(word => /^(?:issue|issued)$/i.test(word[11]));
        const layoutValidity = [];
        for (const label of issueLabels) {
            const labelY = Number(label[7]), labelH = Number(label[9]);
            const issue = datedWords.find(word => date(word[11])
                && Number(word[7]) >= labelY && Number(word[7]) <= labelY + labelH * 4
                && Number(word[6]) <= Number(label[6]) + labelH
                && Number(word[6]) + Number(word[8]) >= Number(label[6]) - labelH);
            if (!issue) continue;
            const issueYear = Number(date(issue[11]).slice(-4));
            const birth = datedWords.find(word => date(word[11])
                && Number(word[7]) > Number(issue[7]) + Number(issue[9])
                && Math.abs(Number(word[6]) - Number(issue[6])) < Number(issue[9]) * 2
                && Number(date(word[11]).slice(-4)) <= issueYear - 14);
            if (!birth) continue;
            const candidates = datedWords.filter(word => Number(word[6]) > Number(issue[6]) + Number(issue[8])
                && Math.abs(Number(word[7]) - Number(issue[7])) < Math.max(Number(word[9]), Number(issue[9])) * 2);
            if (candidates.length === 1) {
                const word = candidates[0];
                layoutValidity.push({x: Number(word[6]), y: Number(word[7]), width: Number(word[8]), height: Number(word[9]), labelled: true, afterYear: issueYear});
            }
        }
        if (layoutValidity.length === 1) return layoutValidity;
        // If a faint label disappeared, revisit the area above each detected
        // date. Its label must be read before the date can become an expiry.
        return datedWords.map(word => ({x: Number(word[6]), y: Number(word[7]), width: Number(word[8]), height: Number(word[9]), labelled: false}));
    }

    function rcIdentity(text) {
        const lines = digits(text).split(/[\r\n]+/).map(line => line.trim()).filter(Boolean);
        const registrations = [];
        lines.forEach((_, index) => addLabelledVehicleRegistrationCandidates(lines, index, registrations));
        const identity = {registration: unique(registrations)[0] || '', chassis: '', engine: ''};
        for (const line of lines) {
            for (const key of ['chassis', 'engine']) {
                const match = line.match(new RegExp('\\b' + key + '\\s*(?:no\\.?|number)?\\s*[:.-]?\\s*([A-Z0-9]{6,25})\\b', 'i'));
                if (match) identity[key] = match[1].toUpperCase();
            }
        }
        return identity;
    }
    function matchRcIdentity(front, back) {
        let matched = false;
        for (const key of ['registration', 'chassis', 'engine']) {
            const a = String(front && front[key] || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            const b = String(back && back[key] || '').replace(/[^A-Za-z0-9]/g, '').toUpperCase();
            if (!a || !b) continue;
            if (a !== b) return {valid: false, reason: 'mismatch'};
            matched = true;
        }
        return {valid: matched, reason: matched ? '' : 'unverifiable'};
    }
    return {parse, date, validateDocument, matchingSideNumber, repeatedAadhaarNumber, licenseValidityRegions, licenseNameRegions, rcIdentity, matchRcIdentity};
});
