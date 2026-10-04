@if ($side === 'left')
    <svg class="reading-illustration reading-illustration--left" viewBox="0 0 400 460" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
        <defs>
            <linearGradient id="reader-left-book" x1="44" y1="322" x2="304" y2="388" gradientUnits="userSpaceOnUse">
                <stop stop-color="#716DFF"/>
                <stop offset="1" stop-color="#393CA2"/>
            </linearGradient>
            <linearGradient id="reader-left-top" x1="90" y1="248" x2="301" y2="285" gradientUnits="userSpaceOnUse">
                <stop stop-color="#B4B4FF"/>
                <stop offset="1" stop-color="#777BF1"/>
            </linearGradient>
            <linearGradient id="reader-left-shirt" x1="166" y1="142" x2="226" y2="239" gradientUnits="userSpaceOnUse">
                <stop stop-color="#8D8AFF"/>
                <stop offset="1" stop-color="#555DCA"/>
            </linearGradient>
            <linearGradient id="reader-left-page" x1="242" y1="162" x2="242" y2="217" gradientUnits="userSpaceOnUse">
                <stop stop-color="#F6F4FF"/>
                <stop offset="1" stop-color="#D3D5FF"/>
            </linearGradient>
        </defs>

        <ellipse cx="189" cy="397" rx="154" ry="18" fill="#090E3E" fill-opacity=".32"/>
        <!-- A small library, big enough to sit on. -->
        <path d="M52 345 286 345 320 326 86 326Z" fill="#9697F4"/>
        <path d="M52 345H286V388H52C44 388 40 382 40 367S44 345 52 345Z" fill="url(#reader-left-book)"/>
        <path d="M286 345 320 326V367L286 388Z" fill="#333B93"/>
        <path d="M58 354H144V379H58C55 379 53 375 53 366S55 354 58 354Z" fill="#F1F0FF"/>
        <path d="M166 348V385M173 348V385" stroke="#ABABFF" stroke-width="3" opacity=".5"/>
        <path d="M61 361H132M60 368H128M60 374H137" stroke="#D5D8F0" stroke-width="1.5"/>

        <path d="M61 305H274L302 288H89Z" fill="#B8C5ED"/>
        <path d="M61 305H274V344H61C53 344 50 336 50 324S53 305 61 305Z" fill="#374B76"/>
        <path d="M68 311H261V338H68C64 338 62 334 62 324S64 311 68 311Z" fill="#EDF3F5"/>
        <path d="M274 305 302 288V329L274 344Z" fill="#263A62"/>
        <path d="M78 317H251M75 323H244M77 330H253" stroke="#C8D4E1" stroke-width="1.5"/>
        <path d="M223 312V338L232 331 240 338V312" fill="#79C9BD"/>

        <path d="M85 264H298L328 246H115Z" fill="url(#reader-left-top)"/>
        <path d="M85 264H298V304H85C77 304 73 296 73 284S77 264 85 264Z" fill="#6465CE"/>
        <path d="M298 264 328 246V286L298 304Z" fill="#4A50B3"/>
        <path d="M91 271H182V297H91C87 297 85 292 85 284S87 271 91 271Z" fill="#F2F1FF"/>
        <path d="M94 278H170M95 284H173M94 290H165" stroke="#D4D5F1" stroke-width="1.5"/>
        <rect x="226" y="275" width="42" height="5" rx="2.5" fill="#9E9DEC"/>
        <rect x="226" y="287" width="27" height="4" rx="2" fill="#9E9DEC"/>

        <!-- Reader facing the centre of the page. -->
        <path d="M169 217C160 228 144 234 132 241L108 279 91 272 113 223C123 211 149 197 169 195Z" fill="#9396ED"/>
        <path d="M166 215C164 230 184 237 205 238L165 263 139 262 139 246 154 242C136 239 128 233 132 221 135 212 150 207 166 207Z" fill="#727DCC"/>
        <path d="m91 269 20 10-5 13-14-2-13-8Z" fill="#F0BBA9"/>
        <path d="m79 280 25 9 6 7c2 3-1 7-5 7l-35-10c-4-1-5-5-2-8l7-7Z" fill="#EEF0FF"/>
        <path d="m140 247 1 17-12 1-7-15Z" fill="#F0BBA9"/>
        <path d="m123 249 9 15 8 2c4 1 3 7-1 8l-25-3c-6-1-6-5-5-9l3-11Z" fill="#EEF0FF"/>
        <path d="M167 143C145 149 138 172 140 192L153 219C167 229 191 229 209 219L204 169C201 152 188 142 167 143Z" fill="url(#reader-left-shirt)"/>
        <path d="M153 161C148 176 151 191 158 201" stroke="#A5A4FF" stroke-width="3" stroke-linecap="round"/>
        <path d="m176 128-1 20c5 8 17 10 24 2l-8-25Z" fill="#EAA990"/>
        <path d="M174 98C184 91 204 98 208 111l4 15-7 3c-1 14-7 18-15 16-14-4-23-16-24-29Z" fill="#FACBB4"/>
        <path d="M169 125c-10-6-13-17-9-27 3-8 10-11 19-10 9-8 22-5 27 3 5 7 2 14-2 19l-9-6-9 5-9-2-1 19Z" fill="#26344F"/>
        <path d="M176 119c-3-6-9-5-9 1 0 5 4 8 8 7" fill="#FACBB4"/>
        <path d="m197 119 2 1" stroke="#493A40" stroke-width="2.5" stroke-linecap="round"/>
        <path d="M190 133c3 2 6 2 8 0" stroke="#C57672" stroke-width="1.7" stroke-linecap="round"/>
        <path d="M190 160c6 11 10 22 13 34l28-8 4 11-36 14c-7 2-10-1-12-6l-13-34" fill="#FACBB4"/>
        <path d="M183 153c8 0 16 13 20 23l-23 9-10-22Z" fill="#8380E8"/>

        <!-- Open book and hands. -->
        <path d="m210 166 28 9 33-15-4 44-31 15-30-10Z" fill="#C2C3FF"/>
        <path d="m214 163 25 8 27-14-3 41-27 15-26-8Z" fill="url(#reader-left-page)"/>
        <path d="m239 171-3 42" stroke="#A7AFE6" stroke-width="2"/>
        <path d="m218 174 14 4m-14 3 13 4m-14 3 13 4m15-14 15-8m-16 16 15-8m-15 15 14-7" stroke="#ADB5E5" stroke-width="1.5" stroke-linecap="round"/>
        <path d="M230 190c3-5 9-4 11-1 2 4-3 8-10 11l-5-6Z" fill="#FACBB4"/>

        <g class="illustration-float">
            <path d="m296 122 38-22 14 8-38 23Z" fill="#B2B7FF"/>
            <path d="m296 122 14 9v49l-14-8Z" fill="#7479E8"/>
            <path d="m310 131 38-23v50l-38 22Z" fill="#8D95FF"/>
            <path d="m321 136 17-10v20l-17 10Z" fill="#E0E6FF"/>
            <path d="m314 170 28-16" stroke="#CBD0FF" stroke-width="2"/>
        </g>
        <g class="illustration-spark" stroke="#98F1D2" stroke-width="2" stroke-linecap="round">
            <path d="M80 170v14m-7-7h14"/>
        </g>
        <g class="illustration-spark" fill="#B2BCFF">
            <circle cx="292" cy="74" r="3"/>
            <circle cx="56" cy="238" r="2"/>
        </g>
        <path d="m106 80 20-12" stroke="#A8B7EF" stroke-width="5" stroke-linecap="round" opacity=".65"/>
        <path d="m96 86 3-2" stroke="#A8B7EF" stroke-width="4" stroke-linecap="round" opacity=".3"/>
    </svg>
@else
    <svg class="reading-illustration reading-illustration--right" viewBox="0 0 400 460" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
        <defs>
            <linearGradient id="reader-right-shirt" x1="212" y1="253" x2="252" y2="379" gradientUnits="userSpaceOnUse">
                <stop stop-color="#8C8CEB"/>
                <stop offset="1" stop-color="#555DAF"/>
            </linearGradient>
            <linearGradient id="reader-right-cover" x1="94" y1="341" x2="243" y2="371" gradientUnits="userSpaceOnUse">
                <stop stop-color="#85DACA"/>
                <stop offset="1" stop-color="#4B9CAA"/>
            </linearGradient>
            <linearGradient id="reader-right-book" x1="292" y1="180" x2="330" y2="261" gradientUnits="userSpaceOnUse">
                <stop stop-color="#A7ADFF"/>
                <stop offset="1" stop-color="#6167D9"/>
            </linearGradient>
        </defs>

        <ellipse cx="223" cy="399" rx="132" ry="17" fill="#090E3E" fill-opacity=".28"/>

        <g class="illustration-float">
            <path d="m115 92 37-22 16 10-38 22Z" fill="#B0B9FF"/>
            <path d="m115 92 15 10v61l-15-9Z" fill="#6975D7"/>
            <path d="m130 102 38-22v61l-38 22Z" fill="#8D9CF5"/>
            <path d="m137 111 23-13v24l-23 14Z" fill="#CCD6FF"/>
            <path d="m142 153 16-9" stroke="#D0D9FF" stroke-width="2" stroke-linecap="round"/>
            <path d="m119 90 33-19 10 6-33 20Z" fill="#F5F5FF"/>
            <path d="m122 90 29-16" stroke="#D0D6EF" stroke-width="1.5"/>
        </g>

        <g class="illustration-float">
            <path d="m281 169 44-26 25 15-45 26Z" fill="#A2ABFA"/>
            <path d="m281 169 24 15v88l-24-14Z" fill="#666ED2"/>
            <path d="m305 184 45-26v88l-45 26Z" fill="url(#reader-right-book)"/>
            <path d="m286 167 38-22 18 10-39 23Z" fill="#F0F1FF"/>
            <path d="m290 167 34-19m-29 22 33-19" stroke="#CBD2EE" stroke-width="1.5"/>
            <path d="m286 180 12 7v70l-12-7Z" fill="#A6AFFF"/>
            <path d="m318 190 20-11v44l-20 11Z" fill="#C6CEFF" fill-opacity=".5"/>
            <path d="m293 252 5 3" stroke="#E3E5FF" stroke-width="2"/>
        </g>

        <!-- Seated reader with a book open across their knees. -->
        <path d="M206 347c-26 6-41 24-46 39h151c-1-30-19-41-49-41Z" fill="#313E71"/>
        <path d="M232 359c-26 2-47 12-55 27h66l33-19Z" fill="#435288"/>
        <path d="M216 251c-22 7-37 24-42 49l-13 45c18 27 67 35 96 12l14-55c6-24-18-56-55-51Z" fill="url(#reader-right-shirt)"/>
        <path d="m220 231-6 28c7 10 22 11 29 0l-5-29Z" fill="#E9A48E"/>
        <path d="M202 194c5-17 23-22 40-11 15 10 17 40 3 55-6 7-14 9-24 4-17-8-23-31-19-48Z" fill="#FAC8AD"/>
        <path d="M201 223c-14-20-11-47 7-56 13-6 25 3 36 0 3-1 6-4 9-8 10 9 12 26 0 38-8 4-25 5-36-4 1 11-4 19-9 24Z" fill="#253849"/>
        <path d="M211 215c-2-8-12-10-13-1-1 6 5 12 12 9" fill="#FAC8AD"/>
        <path d="m225 210 1 3m18-3 1 3" stroke="#52424A" stroke-width="2.7" stroke-linecap="round"/>
        <path d="m235 213 2 8-4 1" stroke="#D49380" stroke-width="1.8" stroke-linecap="round"/>
        <path d="M228 229c4 3 8 3 12-1" stroke="#BC7270" stroke-width="2" stroke-linecap="round"/>
        <path d="M184 283c-8 9-14 31-17 40l-33-8-7 14 43 17c6 1 11-1 14-7l20-43" fill="#F2BBA3"/>
        <path d="M197 264c-10 2-18 14-23 29l23 12 16-28Z" fill="#9292EE"/>
        <path d="M249 280c7 13 9 28 8 41l-31 13 5 16 44-15c5-2 8-6 7-13l-9-42Z" fill="#FAC8AD"/>
        <path d="M247 264c16-4 26 9 29 25l-26 7-11-19Z" fill="#7A7AD1"/>

        <path d="m96 326 63 12 39-28 66 23-68 47-33-5-67-36Z" fill="url(#reader-right-cover)"/>
        <path d="m105 318 57 16 37-29 56 22-62 42-31-3-61-33Z" fill="#DCE9E8"/>
        <path d="m108 310 54 21 39-30 53 19-62 41-30-4-61-33Z" fill="#F8F6EF"/>
        <path d="m162 331 30 30" stroke="#C9D4D5" stroke-width="2"/>
        <path d="m119 324 26 11m-22-4 29 12m-20-2 27 12m22-22 27-19m-19 26 30-20m-24 27 31-21" stroke="#D0D9D9" stroke-width="2" stroke-linecap="round"/>
        <path d="M138 316c-6-7-13-9-16-6-4 4 0 10 11 16l10 2 2-6Z" fill="#F2BBA3"/>
        <path d="M226 335c-8-1-19 5-19 10 0 4 9 7 18 2l9-7Z" fill="#FAC8AD"/>

        <g class="illustration-spark" stroke="#A3F0DA" stroke-width="2" stroke-linecap="round">
            <path d="M256 85v14m-7-7h14"/>
        </g>
        <g class="illustration-spark" stroke="#A7B3FC" stroke-width="2" stroke-linecap="round">
            <path d="M107 239v10m-5-5h10"/>
        </g>
        <circle cx="331" cy="312" r="3" fill="#A6B3FA"/>
        <circle cx="191" cy="124" r="2.5" fill="#A8DCCF"/>
        <path d="m317 110 14-8" stroke="#A5BBFF" stroke-width="4" stroke-linecap="round" opacity=".7"/>
    </svg>
@endif
