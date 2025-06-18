import * as d3 from 'd3';

export default function specimenLinks() {
    const linkContainer = document.getElementById('specimenLinks');
    const targetContainer = document.getElementById('linksPlot');
    if (!linkContainer || !linkContainer.dataset.source) {
        console.log('pee');
        return;
    }

    fetch(linkContainer.dataset.source)
        .then(response => response.json())
        .then(data => drawGraph(targetContainer, data))
        .catch(err => {
            targetContainer.innerText = 'Unable to load data';
        });
}

function drawGraph(container, data) {
    const width = container.offsetWidth;
    const height = 300;

    const svg = d3.select(container)
        .append('svg')
        .attr('width', width)
        .attr('height', height);

    const defs = svg.append('defs');

    defs.append('marker')
        .attr('id', 'arrowhead')
        .attr('viewBox', '0 -5 10 10')
        .attr('refX', 20)
        .attr('refY', 0)
        .attr('markerWidth', 6)
        .attr('markerHeight', 6)
        .attr('orient', 'auto')
        .attr('markerUnits', 'strokeWidth')
        .append('path')
        .attr('d', 'M0,-5L10,0L0,5')
        .attr('fill', '#aaa');

    const simulation = d3.forceSimulation(data.nodes)
        .force('link', d3.forceLink(data.links).id(d => d.id).distance(120))
        .force('charge', d3.forceManyBody().strength(-300))
        .force('center', d3.forceCenter(width / 2, height / 2));

    const link = svg.append('g')
        .attr('stroke', '#aaa')
        .selectAll('line')
        .data(data.links)
        .join('line')
        .attr('stroke-width', 2)
        .attr('marker-end', 'url(#arrowhead)'); // <- ŠIPKA

    const linkPathGroup = svg.append('g')
        .attr('class', 'link-paths');

    const linkPaths = linkPathGroup.selectAll('path')
        .data(data.links)
        .join('path')
        .attr('id', (d, i) => `linkPath${i}`)
        .attr('fill', 'none');

    const linkLabels = svg.append("g")
        .attr("class", "link-labels")
        .selectAll("text")
        .data(data.links)
        .join("text")
        .attr('font-size', 14)   // zvětšené písmo
        .attr('fill', '#555')
        .attr('dy', 15)          // odsazení dolů od linky (u textu mimo tspan funguje lépe)
        .append('textPath')
        .attr('xlink:href', (d, i) => `#linkPath${i}`)
        .attr('startOffset', '50%')
        .style('text-anchor', 'middle')
        .html(d => {
            // split text in half
            const txt = d.relation || '';
            if (txt.length > 12) {
                const mid = Math.floor(txt.length / 2);
                let splitIndex = txt.indexOf(' ', mid);
                if (splitIndex === -1 || splitIndex > txt.length - 4) {
                    splitIndex = txt.lastIndexOf(' ', mid);
                }
                if (splitIndex === -1) splitIndex = mid;

                const firstLine = txt.substring(0, splitIndex).trim();
                const secondLine = txt.substring(splitIndex).trim();

                return `${firstLine}<tspan x="0" dy="1.2em">${secondLine}</tspan>`;
            } else {
                return txt;
            }
        });

    const node = svg.append('g')
        .attr('stroke', '#fff')
        .attr('stroke-width', 1.5)
        .selectAll('circle')
        .data(data.nodes)
        .join('circle')
        .attr('r', 8)
        .attr('fill', d => d.id === data.startId ? '#d32f2f' : '#1976d2')
        .call(drag(simulation));

    const label = svg.append('g')
        .selectAll('a')
        .data(data.nodes)
        .join('a')
        .attr('xlink:href', d => `/detail/${d.id}`)
        .append('text')
        .text(d => d.label)
        .attr('x', 12)
        .attr('y', 3)
        .attr('font-size', '12px')
        .style('cursor', 'pointer')
        .style('fill', '#1976d2')
        .style('text-decoration', 'underline');

    simulation.on('tick', () => {
        link
            .attr('x1', d => d.source.x)
            .attr('y1', d => d.source.y)
            .attr('x2', d => d.target.x)
            .attr('y2', d => d.target.y);

        linkPaths
            .attr('d', d => {
                // if target if left from source, switch them in v path, to keep the line always left-to-right
                if (d.target.x < d.source.x) {
                    return `M${d.target.x},${d.target.y} L${d.source.x},${d.source.y}`;
                }
                return `M${d.source.x},${d.source.y} L${d.target.x},${d.target.y}`;
            });

        node
            .attr('cx', d => d.x)
            .attr('cy', d => d.y);

        label
            .attr('x', d => d.x + 10)
            .attr('y', d => d.y);
    });
}



function drag(simulation) {
    return d3.drag()
        .on('start', (event, d) => {
            if (!event.active) simulation.alphaTarget(0.3).restart();
            d.fx = d.x;
            d.fy = d.y;
        })
        .on('drag', (event, d) => {
            d.fx = event.x;
            d.fy = event.y;
        })
        .on('end', (event, d) => {
            if (!event.active) simulation.alphaTarget(0);
            d.fx = null;
            d.fy = null;
        });
}



