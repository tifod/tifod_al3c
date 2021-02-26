import { Component, OnInit } from '@angular/core';

import { posts as POST_LIST } from './data.json';

@Component({
    selector: 'app-tree-view',
    template: `
    <p>
      tree-view works!
    </p>
  `,
    styles: [
    ]
})
export class TreeViewComponent implements OnInit {
    constructor() { }

    ngOnInit(): void {
    }

}
