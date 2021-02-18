## Postgres  benchmark attribute-value table vs JSONB for store user-defined forms data
[ReadOnly]

**Require PHP >=7.4; composer; docker**

### Usage
 - clone repo
 - run `make init` for install dependencies and run docker
 - run `make migrate` for prepare database structure
 - run `make seed` for fill databases with same fake data
 - run `make bench` for run test queries across all databases


 #### Results
##### Legend:
  simple - stored in table (field, value)
  typed  - stored in table (field, value_bool, value_int, value_str, value_date... etc)
  json - not indexed jsonb column
  json_indx - btree indexed jsonb column

[Show used queries](./app/queries)

| query type                               |  pg10   | pg11   | pg12   | pg13   |
|--------------------------------------|--------|--------|--------|--------|
|Sort|||||
| typed     | 0.143  | 0.1435 | 0.1246 | 0.1212 |
| simple    | 0.1391 | 0.151  | 0.1229 | 0.1022 |
| json      | 0.0982 | 0.0973 | 0.0949 | 0.0933 |
| json_indx | 0.1    | 0.0963 | 0.0949 | 0.0928 |
| Multi sort|||||
| typed     | 0.185  | 0.1856 | 0.1655 | 0.1675 |
| simple    | 0.1971 | 0.1853 | 0.1657 | 0.1382 |
| json      | 0.1243 | 0.1148 | 0.1138 | 0.1124 |
| json_indx | 0.13   | 0.1156 | 0.1147 | 0.1119 |
| Date filter|||||
| typed     | 0.1305 | 0.1356 | 0.112  | 0.1154 |
| simple    | 0.1492 | 0.1439 | 0.1165 | 0.0931 |
| json      | 0.055  | 0.0622 | 0.051  | 0.0542 |
| json_indx | 0.056  | 0.0513 | 0.0515 | 0.0517 |
| Json filter|||||
| typed     | 0.1286 | 0.1209 | 0.1057 | 0.1073 |
| simple    | 0.1417 | 0.1291 | 0.1094 | 0.0861 |
| json      | 0.0152 | 0.0163 | 0.014  | 0.0174 |
| json_indx | 0.0186 | 0.0212 | 0.015  | 0.0143 |
| Foreign key inject|||||
| typed     | 0.1748 | 0.0953 | 0.0906 | 0.1419 |
| simple    | 0.1679 | 0.1653 | 0.1471 | 0.1199 |
| json      | 0.1197 | 0.1124 | 0.111  | 0.1107 |
| json_indx | 0.1169 | 0.1129 | 0.1106 | 0.1094 |
| Group by count|||||
| typed     | 0.146  | 0.1454 | 0.1334 | 0.1273 |
| simple    | 0.1532 | 0.1489 | 0.1293 | 0.1012 |
| json      | 0.0178 | 0.0139 | 0.0194 | 0.014  |
| json_indx | 0.0173 | 0.0144 | 0.0143 | 0.0142 |
| Filters by bool,json,int sort by int|||||
| typed     | 0.0913 | 0.0919 | 0.0816 | 0.0805 |
| simple    | 0.142  | 0.1315 | 0.1142 | 0.0993 |
| json      | 0.0474 | 0.0399 | 0.0391 | 0.0378 |
| json_indx | 0.0462 | 0.04   | 0.0388 | 0.0389 |
| Inject fk, filter and sort by date|||||
| typed     | 0.0396 | 0.034  | 0.0902 | 0.0366 |
| simple    | 0.1656 | 0.1446 | 0.1269 | 0.1139 |
| json      | 0.0472 | 0.0398 | 0.0389 | 0.0393 |
| json_indx | 0.0408 | 0.0432 | 0.0469 | 0.0391 |
| Average from filtered|||||
| typed     | 0.0896 | 0.0783 | 0.0717 | 0.0683 |
| simple    | 0.1317 | 0.1196 | 0.1034 | 0.0914 |
| json      | 0.0246 | 0.0131 | 0.0133 | 0.0133 |
| json_indx | 0.0285 | 0.0137 | 0.0135 | 0.0132 |
| Filter greater than average value|||||
| typed     | 0.1983 | 0.1797 | 0.1604 | 0.1597 |
| simple    | 0.2658 | 0.2457 | 0.2119 | 0.1736 |
| json      | 0.0463 | 0.0355 | 0.0351 | 0.0341 |
| json_indx | 0.051  | 0.0369 | 0.0353 | 0.0344 |
